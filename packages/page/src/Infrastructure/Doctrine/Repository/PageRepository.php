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
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Exception\PageNotFoundException;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Webmozart\Assert\Assert;

class PageRepository implements PageRepositoryInterface
{
    /**
     * TODO it should be possible to extend fields and groups inside the SELECTS.
     */
    private const SELECTS = [
        // GROUPS
        self::GROUP_SELECT_PAGE_ADMIN => [
            self::SELECT_PAGE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true,
            ],
        ],
        self::GROUP_SELECT_PAGE_WEBSITE => [
            self::SELECT_PAGE_CONTENT => [
                DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
            ],
        ],
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var NestedTreeRepository<PageInterface>
     */
    protected $entityRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    protected $entityDimensionContentRepository;

    /**
     * @var DimensionContentQueryEnhancer
     */
    protected $dimensionContentQueryEnhancer;

    /**
     * @var class-string<PageInterface>
     */
    protected $pageClassName;

    /**
     * @var class-string<PageDimensionContentInterface>
     */
    protected $pageDimensionContentClassName;

    public function __construct(
        EntityManagerInterface $entityManager,
        DimensionContentQueryEnhancer $dimensionContentQueryEnhancer
    ) {
        $repository = $entityManager->getRepository(PageInterface::class);
        Assert::isInstanceOf($repository, NestedTreeRepository::class);

        $this->entityRepository = $repository;
        $this->entityDimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);
        $this->entityManager = $entityManager;
        $this->dimensionContentQueryEnhancer = $dimensionContentQueryEnhancer;
        $this->pageClassName = $this->entityRepository->getClassName();
        $this->pageDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function createNew(?string $uuid = null): PageInterface
    {
        $className = $this->pageClassName;

        return new $className($uuid);
    }

    public function getOneBy(array $filters, array $selects = []): PageInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var PageInterface $page */
            $page = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            throw new PageNotFoundException($filters, 0, $e);
        }

        return $page;
    }

    public function findOneBy(array $filters, array $selects = []): ?PageInterface
    {
        $queryBuilder = $this->createQueryBuilder($filters, [], $selects);

        try {
            /** @var PageInterface $page */
            $page = $queryBuilder->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }

        return $page;
    }

    public function countBy(array $filters = []): int
    {
        // The countBy method will ignore any page and limit parameters
        // for better developer experience we will strip them away here
        // instead of that the developer need to take that into account
        // in there call of the countBy method.
        unset($filters['page']); // @phpstan-ignore-line
        unset($filters['limit']); // @phpstan-ignore-line

        $queryBuilder = $this->createQueryBuilder($filters);

        $queryBuilder->select('COUNT(DISTINCT page.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return \Generator<PageInterface>
     */
    public function findBy(array $filters = [], array $sortBy = [], array $selects = []): \Generator
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        /** @var iterable<PageInterface> $pages */
        $pages = $queryBuilder->getQuery()->getResult();

        foreach ($pages as $page) {
            yield $page;
        }
    }

    public function findIdentifiersBy(array $filters = [], array $sortBy = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy);

        $queryBuilder->select('DISTINCT page.uuid');

        // we need to select the fields which are used in the order by clause
        /** @var OrderBy[] $orderBys */
        $orderBys = $queryBuilder->getDQLPart('orderBy');
        foreach ($orderBys as $orderBy) {
            $queryBuilder->addSelect(\explode(' ', $orderBy->getParts()[0])[0]);
        }

        /** @var iterable<string> $identifiers */
        $identifiers = $queryBuilder->getQuery()->getResult();

        return $identifiers;
    }

    public function add(PageInterface $page): void
    {
        $this->entityManager->persist($page);
    }

    public function remove(PageInterface $page): void
    {
        $this->entityManager->remove($page);
    }

    public function findByAsTree(array $filters = [], array $sortBy = [], array $selects = []): iterable
    {
        $queryBuilder = $this->createQueryBuilder($filters, $sortBy, $selects);

        $query = $queryBuilder->getQuery();
        // Hint is necessary for the TreeObjectHydrator to work
        // https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#building-trees-from-your-entities
        $query->setHint(Query::HINT_INCLUDE_META_COLUMNS, true);

        /** @var PageInterface[] $pages */
        $pages = $query->getResult('sulu_page_tree');

        return $pages;
    }

    public function reorderOneBy(array $filters, int $position): void
    {
        $page = $this->getOneBy($filters);

        $parent = $page->getParent();
        $siblings = $this->entityRepository->getChildren($parent);
        $currentPosition = \array_search($page, $siblings);

        if (false === $currentPosition) {
            throw new \RuntimeException(\sprintf('Page with id "%s" not found in sibling list', $page->getId()));
        }

        $currentPosition = \intval($currentPosition);
        $movementSteps = $currentPosition - \max(0, $position - 1);

        if ($movementSteps > 0) {
            $this->entityRepository->moveUp($page, $movementSteps);
        } elseif ($movementSteps < 0) {
            $this->entityRepository->moveDown($page, \abs($movementSteps));
        }
    }

    public function moveOneBy(array $sourceFilters, array $targetParentFilters): void
    {
        $sourcePage = $this->getOneBy($sourceFilters);
        $destinationPage = $this->getOneBy($targetParentFilters);

        if ($sourcePage === $destinationPage) {
            return;
        }

        $this->entityRepository->persistAsLastChildOf($sourcePage, $destinationPage);
    }

    /**
     * @param array{
     *     uuid?: string,
     *     uuids?: string[],
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: int[],
     *     categoryKeys?: string[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     *     parentId?: string|null,
     *     webspaceKey?: string,
     *     page?: int,
     *     limit?: int,
     *     navigationContexts?: string[],
     *     depth?: int,
     * } $filters
     * @param array{
     *     uuid?: 'asc'|'desc',
     *     title?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     * } $sortBy
     * @param array{
     *     page_admin?: bool,
     *     page_website?: bool,
     *     with-page-content?: bool|array<string, mixed>,
     * }|array<string, mixed> $selects
     */
    private function createQueryBuilder(array $filters, array $sortBy = [], array $selects = []): QueryBuilder
    {
        foreach ($selects as $selectGroup => $value) {
            if (!$value) {
                continue;
            }

            if (isset(self::SELECTS[$selectGroup])) {
                $selects = \array_replace_recursive($selects, self::SELECTS[$selectGroup]);
            }
        }
        $queryBuilder = $this->entityRepository->createQueryBuilder('page');

        $uuid = $filters['uuid'] ?? null;
        if (null !== $uuid) {
            Assert::string($uuid); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.uuid = :uuid')
                ->setParameter('uuid', $uuid);
        }

        $uuids = $filters['uuids'] ?? null;
        if (null !== $uuids) {
            Assert::isArray($uuids); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.uuid IN(:uuids)')
                ->setParameter('uuids', $uuids);
        }

        $webspace = $filters['webspaceKey'] ?? null;
        if (null !== $webspace) {
            Assert::string($webspace); // @phpstan-ignore staticMethod.alreadyNarrowedType
            $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspace);
        }

        $parentId = $filters['parentId'] ?? null;
        // null is a valid value for parentId
        if (\array_key_exists('parentId', $filters)) {
            Assert::nullOrString($parentId); // @phpstan-ignore staticMethod.alreadyNarrowedType
            match ($parentId) {
                null => $queryBuilder->andWhere('page.parent IS NULL'),
                default => $queryBuilder->andWhere('page.parent = :parentId')
                    ->setParameter('parentId', $parentId),
            };
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
            (\array_key_exists('locale', $filters)       // should also work with locale = null
            && \array_key_exists('stage', $filters))
            || ([] === $filters && [] !== $sortBy)      // if no filters are set, but sortBy is set, we need to set the sorting
        ) {
            $this->dimensionContentQueryEnhancer->addFilters(
                $queryBuilder,
                'page',
                $this->pageDimensionContentClassName,
                $filters,
                $sortBy
            );
        }

        if ([] !== $sortBy) {
            foreach ($sortBy as $field => $order) {
                if ('uuid' === $field) {
                    $queryBuilder->addOrderBy('page.uuid', $order);
                } elseif ('created' === $field) {
                    $queryBuilder->addOrderBy('page.created', $order);
                }
            }
        }

        // selects
        if ($selects[self::SELECT_PAGE_CONTENT] ?? null) {
            /** @var array<string, bool> $contentSelects */
            $contentSelects = $selects[self::SELECT_PAGE_CONTENT];
            $this->leftJoinDimensionContent($queryBuilder);

            $this->dimensionContentQueryEnhancer->addSelects(
                $queryBuilder,
                $this->pageDimensionContentClassName,
                $filters,
                $contentSelects
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
