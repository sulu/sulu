<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\WebspaceSettingDimensionContentInterface;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;

/**
 * @phpstan-type WebspaceSetting array{
 *     webspaceKey: string,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     locale: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminWebspaceSettingReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<WebspaceSettingDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->dimensionContentRepository = $entityManager->getRepository(WebspaceSettingDimensionContentInterface::class);
    }

    public function total(): ?int
    {
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        foreach ($this->loadWebspaceSettings($reindexConfig->getIdentifiers()) as $webspaceSetting) {
            yield [
                'id' => WebspaceSettingInterface::RESOURCE_KEY . '__' . $webspaceSetting['webspaceKey'] . '__' . $webspaceSetting['locale'],
                'resourceKey' => WebspaceSettingInterface::RESOURCE_KEY,
                'resourceId' => $webspaceSetting['webspaceKey'],
                'changedAt' => $webspaceSetting['changed']->format('c'),
                'createdAt' => $webspaceSetting['created']->format('c'),
                'title' => $webspaceSetting['webspaceKey'],
                'locale' => $webspaceSetting['locale'],
                'metadata' => [
                    'webspaceKey' => $webspaceSetting['webspaceKey'],
                ],
                'securityContext' => WebspaceSettingAdmin::getSecurityContext($webspaceSetting['webspaceKey']),
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<WebspaceSetting>
     */
    private function loadWebspaceSettings(array $identifiers = []): iterable
    {
        $queryBuilder = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->select('webspaceSetting.webspaceKey')
            ->addSelect('dimensionContent.created')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.locale')
            ->innerJoin('dimensionContent.webspaceSetting', 'webspaceSetting')
            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version')
            ->setParameter('stage', DimensionContentInterface::STAGE_DRAFT)
            ->setParameter('version', DimensionContentInterface::CURRENT_VERSION);

        if ([] !== $identifiers) {
            $conditions = [];
            foreach ($identifiers as $index => $identifier) {
                [$resourceKey, $id, $locale] = \explode('__', $identifier, 3) + ['', '', ''];

                if (WebspaceSettingInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $conditions[] = "(webspaceSetting.webspaceKey = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $queryBuilder->setParameter("id{$index}", $id);
                $queryBuilder->setParameter("locale{$index}", $locale);
            }

            if ([] === $conditions) {
                return [];
            }

            $queryBuilder->andWhere(\implode(' OR ', $conditions));
        }

        /** @var iterable<WebspaceSetting> */
        return $queryBuilder->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
