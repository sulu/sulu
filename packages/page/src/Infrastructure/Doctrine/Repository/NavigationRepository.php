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
use Doctrine\ORM\Query\Expr\Join;
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
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findByAsTree([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
        ]);

        $loadExcerpt = (bool) ($properties['excerpt'] ?? false);

        return $this->normalizePageTree($pages, $loadExcerpt, $locale, 1, $depth);
    }

    public function getNavigationFlat(
        string $navigationContext,
        string $locale,
        string $webspaceKey,
        int $depth = 1,
        array $properties = []
    ): array {
        $pages = $this->findBy([
            'locale' => $locale,
            'navigationContexts' => [$navigationContext],
            'depth' => $depth,
            'webspaceKey' => $webspaceKey,
        ]);

        $loadExcerpt = (bool) ($properties['excerpt'] ?? false);

        $result = [];
        /** @var PageInterface $page */
        foreach ($pages as $page) {
            $content = $this->resolvePageContent($page, $locale);
            $result[] = $this->normalizePageContent($content, $loadExcerpt);
        }

        return $result;
    }

    /**
     * @param array{
     *      locale?: string|null,
     *      stage?: string|null,
     *      webspaceKey?: string,
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
        $queryBuilder = $this->createQueryBuilder($filters);

        /** @var iterable<PageInterface> $pages */
        $pages = $queryBuilder->getQuery()->getResult();

        foreach ($pages as $page) {
            yield $page;
        }
    }

    /**
     * @param array{
     *      locale?: string|null,
     *      stage?: string|null,
     *      webspaceKey?: string,
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

        return $pages;
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
        $result = [...$contentData];

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
     * } $filters
     */
    private function createQueryBuilder(array $filters): QueryBuilder
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

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
                $this->leftJoinDimensionContent($queryBuilder);

                $queryBuilder->leftJoin('dimensionContent.navigationContexts', 'navigationContext')
                    ->andWhere('navigationContext.navigationContext IN (:navigationContexts)')
                    ->setParameter('navigationContexts', $navigationContexts);
            }
        }

        $queryBuilder->addOrderBy('page.lft', 'asc');

        return $queryBuilder;
    }

    private function leftJoinDimensionContent(QueryBuilder $queryBuilder): void
    {
        // check if we already have a join for dimensionContent
        $hasJoin = false;
        /** @var array<string, Join[]> $joinParts */
        $joinParts = $queryBuilder->getDQLPart('join');

        foreach ($joinParts as $joins) {
            foreach ($joins as $join) {
                if ('page.dimensionContents' === $join->getJoin()) {
                    $hasJoin = true;
                    break 2;
                }
            }
        }

        if (!$hasJoin) {
            $queryBuilder->leftJoin('page.dimensionContents', 'dimensionContent');
        }
    }
}
