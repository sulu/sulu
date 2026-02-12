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
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexProviderEnhancerInterface;

/**
 * @phpstan-type PageData array{
 *     pageId: int,
 *     changed: \DateTimeImmutable,
 *     created: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 *     slug: string,
 *     authored: \DateTimeImmutable|null,
 *     webspaceKey: string,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class WebsitePageReindexProvider implements ReindexProviderInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    /**
     * @param iterable<WebsitePageReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private iterable $enhancers = [],
    ) {
        $dimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);

        $this->dimensionContentRepository = $dimensionContentRepository;
    }

    public function total(): ?int
    {
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $identifiers = $reindexConfig->getIdentifiers();
        $offset = 0;
        $batch = $this->loadBatch($identifiers, $offset);

        while ([] !== $batch) {
            /** @var PageData $page */
            foreach ($batch as $page) {
                $authoredAt = $page['authored'] ?? $page['changed'];

                $data = [
                    'id' => PageInterface::RESOURCE_KEY . '__' . ((string) $page['pageId']) . '__' . $page['locale'],
                    'resourceKey' => PageInterface::RESOURCE_KEY,
                    'resourceId' => (string) $page['pageId'],
                    'locale' => $page['locale'],
                    'webspaces' => [$page['webspaceKey']],
                    'title' => '',
                    'url' => $page['slug'],
                    'content' => [],
                    'mediaId' => '',
                    'authoredAt' => $authoredAt->format('c'),
                    'metadata' => [],
                ];

                foreach ($this->enhancers as $enhancer) {
                    $data = $enhancer->enhanceDocument($page, $data);
                }

                if ('' === $data['title']) {
                    $data['title'] = $page['title'];
                }

                yield $data;
            }

            $offset += self::BATCH_SIZE;
            $batch = $this->loadBatch($identifiers, $offset);
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return array<int, PageData>
     */
    private function loadBatch(array $identifiers, int $offset): array
    {
        $queryBuilder = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->leftJoin('dimensionContent.route', 'route')
            ->leftJoin('dimensionContent.page', 'page')
            ->leftJoin(
                AccessControl::class,
                'accessControl',
                'WITH',
                'accessControl.entityId = page.uuid AND accessControl.entityClass = :pageClass'
            )
            ->select('IDENTITY(dimensionContent.page) AS pageId')
            ->addSelect('dimensionContent.authored')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('route.slug')
            ->addSelect('page.webspaceKey')
            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version')
            ->andWhere('accessControl.id IS NULL');

        $parameters = [
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
            'pageClass' => Page::class,
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

            $queryBuilder->andWhere(\implode(' OR ', $conditions));
        }

        foreach ($parameters as $parameterKey => $parameterValue) {
            $queryBuilder->setParameter($parameterKey, $parameterValue);
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($queryBuilder);
        }

        $queryBuilder->orderBy('dimensionContent.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(self::BATCH_SIZE);

        /** @var array<int, PageData> */
        return $queryBuilder->getQuery()->getResult();
    }

    public static function getIndex(): string
    {
        return 'website';
    }
}
