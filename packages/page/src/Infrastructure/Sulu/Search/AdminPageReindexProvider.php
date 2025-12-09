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
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;

/**
 * @phpstan-type Page array{
 *     pageId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 *     webspaceKey: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class AdminPageReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<PageInterface>
     */
    protected EntityRepository $pageRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    protected EntityRepository $dimensionContentRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);
        $dimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);

        $this->pageRepository = $repository;
        $this->dimensionContentRepository = $dimensionContentRepository;
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $pages = $this->loadPages($reindexConfig->getIdentifiers());

        /** @var Page $page */
        foreach ($pages as $page) {
            yield [
                'id' => PageInterface::RESOURCE_KEY . '__' . ((string) $page['pageId']) . '__' . $page['locale'],
                'resourceKey' => PageInterface::RESOURCE_KEY,
                'resourceId' => (string) $page['pageId'],
                'changedAt' => $page['changed']->format('c'),
                'createdAt' => $page['created']->format('c'),
                'title' => $page['title'],
                'locale' => $page['locale'],
                'metadata' => [
                    'webspaceKey' => $page['webspaceKey'],
                ],
                'securityContext' => PageAdmin::SECURITY_CONTEXT_PREFIX . $page['webspaceKey'],
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Page>
     */
    private function loadPages(array $identifiers = []): iterable
    {
        $qb = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->leftJoin('dimensionContent.page', 'page')
            ->select('IDENTITY(dimensionContent.page) AS pageId')
            ->addSelect('dimensionContent.created')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('page.webspaceKey')
            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version');

        $parameters = [
            'stage' => DimensionContentInterface::STAGE_DRAFT,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        if (0 < \count($identifiers)) {
            $conditions = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('__', $identifier)[0];

                if (PageInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.page = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->andWhere(\implode(' OR ', $conditions));
        }

        foreach ($parameters as $parameterKey => $parameterValue) {
            $qb->setParameter($parameterKey, $parameterValue);
        }

        /** @var iterable<Page> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'admin';
    }
}
