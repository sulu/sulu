<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Webmozart\Assert\Assert;

class NavigationRepository implements NavigationRepositoryInterface
{
    /**
     * @var NestedTreeRepository<PageInterface>
     */
    protected NestedTreeRepository $entityRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    protected EntityRepository $entityDimensionContentRepository;

    /**
     * @var class-string<PageInterface>
     */
    protected string $pageClassName;

    /**
     * @var class-string<PageDimensionContentInterface>
     */
    protected string $pageDimensionContentClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private ContentAggregatorInterface $contentAggregator,
        private ContentResolverInterface $contentResolver,
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);
        Assert::isInstanceOf($repository, NestedTreeRepository::class);

        $this->entityRepository = $repository;
        $this->entityDimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);
        $this->pageClassName = $this->entityRepository->getClassName();
        $this->pageDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function getNavigationTree(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findByAsTree([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
            'segmentKey' => $segmentKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $loadExcerpt = (bool) ($properties['excerpt'] ?? false);

        return $this->normalizePageTree($pages, $loadExcerpt, $locale, 1, $depth);
    }

    public function getNavigationFlat(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        ?string $segmentKey,
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findBy([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
            'segmentKey' => $segmentKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $this->resolveAndNormalizePages($pages, $locale, $this->shouldLoadExcerpt($properties));
    }

    public function getNavigationFlatByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array {
        $filters = $this->buildChildrenFilters($uuid, $locale, $webspaceKey, $depth, $navigationContext);

        /** @var iterable<PageInterface> $pages */
        $pages = $this->createQueryBuilder($filters)->getQuery()->getResult();

        return $this->resolveAndNormalizePages($pages, $locale, $this->shouldLoadExcerpt($properties));
    }

    public function getNavigationTreeByUuid(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        ?string $navigationContext = null,
        array $properties = []
    ): array {
        $filters = $this->buildChildrenFilters($uuid, $locale, $webspaceKey, $depth, $navigationContext);
        $pages = $this->findByAsTree($filters);

        return $this->normalizePageTree($pages, $this->shouldLoadExcerpt($properties), $locale, 1, $depth);
    }

    public function getBreadcrumb(
        string $uuid,
        string $locale,
        string $webspaceKey,
        array $properties = []
    ): array {
        $page = $this->createQueryBuilder(['uuid' => $uuid])->getQuery()->getOneOrNullResult();

        if (null === $page) {
            return [];
        }

        // Query all ancestors in a single query using nested set values
        /** @var PageInterface[] $ancestors */
        $ancestors = $this->createQueryBuilder([
            'ancestorsOf' => $uuid,
            'webspaceKey' => $webspaceKey,
        ])->getQuery()->getResult();

        // Combine ancestors with current page (ancestors are already ordered by lft ASC)
        /** @var PageInterface[] $pages */
        $pages = [...$ancestors, $page];

        return $this->resolveAndNormalizePages($pages, $locale, $this->shouldLoadExcerpt($properties));
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function shouldLoadExcerpt(array $properties): bool
    {
        return (bool) ($properties['excerpt'] ?? false);
    }

    /**
     * @return array{
     *     locale: string,
     *     webspaceKey: string,
     *     stage: string,
     *     childrenOf: string,
     *     childrenDepth: int,
     *     navigationContexts?: array<string>
     * }
     */
    private function buildChildrenFilters(
        string $uuid,
        string $locale,
        string $webspaceKey,
        int $depth,
        ?string $navigationContext
    ): array {
        $filters = [
            'locale' => $locale,
            'webspaceKey' => $webspaceKey,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'childrenOf' => $uuid,
            'childrenDepth' => $depth,
        ];

        if (null !== $navigationContext) {
            $filters['navigationContexts'] = [$navigationContext];
        }

        return $filters;
    }

    /**
     * @param iterable<PageInterface> $pages
     *
     * @return array<string, mixed>[]
     */
    private function resolveAndNormalizePages(
        iterable $pages,
        string $locale,
        bool $loadExcerpt
    ): array {
        $result = [];
        foreach ($pages as $page) {
            $content = $this->resolvePageContent($page, $locale);
            $result[] = $this->normalizePageContent($content, $loadExcerpt);
        }

        return $result;
    }

    /**
     * @param string[] $fields
     *
     * @return array<string, int|string>|null
     */
    private function fetchNestedSetValues(string $uuid, array $fields): ?array
    {
        $qb = $this->entityRepository->createQueryBuilder('page');
        $qb->select(...\array_map(fn ($f) => "page.{$f}", $fields))
            ->where('page.uuid = :uuid')
            ->setParameter('uuid', $uuid);

        /** @var array<string, int|string>|null $result */
        $result = $qb->getQuery()->getOneOrNullResult();

        return $result;
    }

    /**
     * @param array{
     *      locale?: string|null,
     *      stage?: string|null,
     *      webspaceKey?: string,
     *      segmentKey?: string|null,
     *      page?: int,
     *      limit?: int,
     *      navigationContexts?: string[],
     *      depth?: int,
     *  } $filters
     *
     * @return \Generator<PageInterface>
     */
    private function findBy(array $filters = []): \Generator
    {
        $query = $this->createQueryBuilder($filters)->getQuery();

        /** @var PageInterface $page */
        foreach ($query->toIterable() as $page) {
            yield $page;
        }
    }

    /**
     * @param array{
     *      locale?: string|null,
     *      stage?: string|null,
     *      webspaceKey?: string,
     *      segmentKey?: string|null,
     *      page?: int,
     *      limit?: int,
     *      navigationContexts?: string[],
     *      depth?: int,
     *  } $filters
     *
     * @return \Generator<PageInterface>
     */
    private function findByAsTree(array $filters = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters);

        $query = $queryBuilder->getQuery();
        // Hint is necessary for the TreeObjectHydrator to work
        // https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#building-trees-from-your-entities
        $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

        /** @var iterable<PageInterface> $pages */
        $pages = $query->getResult('sulu_page_tree');

        foreach ($pages as $page) {
            yield $page;
        }
    }

    /**
     * @param iterable<PageInterface> $pages
     *
     * @return array<string, mixed>[]
     */
    private function normalizePageTree(iterable $pages, bool $loadExcerpt, string $locale, int $depth, int $maxDepth): array
    {
        $result = [];
        foreach ($pages as $page) {
            $content = $this->resolvePageContent($page, $locale);
            $normalizedContent = $this->normalizePageContent($content, $loadExcerpt);

            $children = $depth < $maxDepth ? $page->getChildren() : [];
            $normalizedContent['children'] = $this->normalizePageTree($children, $loadExcerpt, $locale, $depth + 1, $maxDepth);

            $result[] = $normalizedContent;
        }

        return $result;
    }

    /**
     * @return array{
     *      resource: object,
     *      content: mixed,
     *      view: mixed[],
     *      extension: array<string, array<string, mixed>>,
     * }
     */
    private function resolvePageContent(PageInterface $page, string $locale): array
    {
        $contentDimension = $this->contentAggregator->aggregate($page, [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        return $this->contentResolver->resolve($contentDimension);
    }

    /**
     * @param array{
     *      resource: object,
     *      content: mixed,
     *      view: mixed[],
     *      extension: array<string, array<string, mixed>>,
     *  } $content
     *
     * @return array<string, mixed>
     */
    private function normalizePageContent(array $content, bool $loadExcerpt): array
    {
        /** @var array{
         *      extension: array<string, array<string, mixed>>,
         * } $contentData
         */
        $contentData = $content['content'];
        /** @var PageInterface $page */
        $page = $content['resource'];
        $result = [
            ...$contentData,
            ...['webspaceKey' => $page->getWebspaceKey()],
        ];

        if ($loadExcerpt) {
            $result['excerpt'] = $content['extension']['excerpt'];
        }

        return $result;
    }

    /**
     * @param array{
     *     locale?: string|null,
     *     stage?: string|null,
     *     webspaceKey?: string,
     *     page?: int,
     *     limit?: int,
     *     navigationContexts?: string[],
     *     depth?: int,
     *     uuid?: string,
     *     ancestorsOf?: string,
     *     childrenOf?: string,
     *     childrenDepth?: int,
     * } $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $ancestorsOf = $filters['ancestorsOf'] ?? null;
        if (null !== $ancestorsOf) {
            Assert::string($ancestorsOf); // @phpstan-ignore staticMethod.alreadyNarrowedType

            $result = $this->fetchNestedSetValues($ancestorsOf, ['lft', 'rgt']);

            if (null !== $result) {
                $queryBuilder
                    ->andWhere('page.lft < :ancestorLft')
                    ->andWhere('page.rgt > :ancestorRgt')
                    ->setParameter('ancestorLft', $result['lft'])
                    ->setParameter('ancestorRgt', $result['rgt']);
            }
        }

        $childrenOf = $filters['childrenOf'] ?? null;
        if (null !== $childrenOf) {
            Assert::string($childrenOf); // @phpstan-ignore staticMethod.alreadyNarrowedType

            $result = $this->fetchNestedSetValues($childrenOf, ['lft', 'rgt', 'depth']);

            if (null !== $result) {
                $queryBuilder
                    ->andWhere('page.lft > :parentLft')
                    ->andWhere('page.rgt < :parentRgt')
                    ->setParameter('parentLft', $result['lft'])
                    ->setParameter('parentRgt', $result['rgt']);

                $childrenDepth = $filters['childrenDepth'] ?? null;
                if (null !== $childrenDepth) {
                    Assert::integer($childrenDepth); // @phpstan-ignore staticMethod.alreadyNarrowedType
                    Assert::integer($result['depth']);
                    $queryBuilder
                        ->andWhere('page.depth <= :maxDepth')
                        ->setParameter('maxDepth', $result['depth'] + $childrenDepth);
                }
            } else {
                // Parent UUID doesn't exist, make query return no results
                $queryBuilder->andWhere('1 = 0');
            }
        }

        $webspace = $filters['webspaceKey'] ?? null;
        if (null !== $webspace) {
            Assert::string($webspace); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspace);
        }

        $depth = $filters['depth'] ?? null;
        if (null !== $depth) {
            Assert::integer($depth); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.depth <= :depth')
                ->setParameter('depth', $depth);
        }

        $limit = $filters['limit'] ?? null;
        if (null !== $limit) {
            Assert::integer($limit); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->setMaxResults($limit);
        }

        $page = $filters['page'] ?? null;
        if (null !== $page) {
            Assert::integer($page); // @phpstan-ignore staticMethod.alreadyNarrowedType
            Assert::notNull($limit);
            $offset = (int) ($limit * ($page - 1));
            $queryBuilder->setFirstResult($offset);
        }

        if (
            \array_key_exists('locale', $filters)       // should also work with locale = null
                && \array_key_exists('stage', $filters)
        ) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'page',
                $this->pageDimensionContentClassName,
                $filters,
                []
            );
        }

        $navigationContexts = $filters['navigationContexts'] ?? null;
        if (null !== $navigationContexts) {
            Assert::isArray($navigationContexts); // @phpstan-ignore staticMethod.alreadyNarrowedType
            if ([] !== $navigationContexts) {
                $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')
                    ->andWhere('navigationContext.navigationContext IN (:navigationContexts)')
                    ->setParameter('navigationContexts', $navigationContexts);
            }
        }

        $queryBuilder->addOrderBy('page.lft', 'asc');

        return $queryBuilder;
    }
}
