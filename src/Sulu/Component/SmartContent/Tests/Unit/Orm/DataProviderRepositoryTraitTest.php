<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Orm;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class Query extends AbstractQuery
{
    public function setFirstResult(): self
    {
        return $this;
    }

    public function setMaxResults(): self
    {
        return $this;
    }

    public function getSQL(): string
    {
        return '';
    }

    public function _doExecute(): int
    {
        return 0;
    }
}

class DataProviderRepositoryTraitTest extends TestCase
{
    use ProphecyTrait;
    use DataProviderRepositoryTrait;

    /**
     * @var ObjectProphecy<QueryBuilder>
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->prophesize(QueryBuilder::class);
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string|null $indexBy
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        return $this->queryBuilder->reveal();
    }

    /**
     * Append joins to query builder for "findByFilters" function.
     *
     * @param string $alias
     * @param string $locale
     */
    protected function appendJoins(QueryBuilder $queryBuilder, $alias, $locale): void
    {
    }

    public function testFindByFiltersIds(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);

        $this->queryBuilder->select(Argument::cetera())->willReturn($this->queryBuilder)->shouldBeCalled();
        $this->queryBuilder->distinct(Argument::cetera())->willReturn($this->queryBuilder)->shouldBeCalled();
        $this->queryBuilder->orderBy(Argument::cetera())->willReturn($this->queryBuilder)->shouldBeCalled();
        $this->queryBuilder->getQuery()->willReturn($query)->shouldBeCalled();
        // using distinct here is essential, since due to our joins multiple rows might be returned
        // this makes problems if also a limit is used
        $this->queryBuilder->distinct()->shouldBeCalled();

        $this->findByFiltersIds([], 1, 5, null, 'de');
    }

    public function testFindByFiltersIdsWithUser(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $accessControlQueryEnhancer = $this->prophesize(AccessControlQueryEnhancer::class);

        $query = $this->prophesize(Query::class);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->distinct(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->orderBy(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query);

        $dataProviderRepositoryTrait = new class($accessControlQueryEnhancer->reveal(), $queryBuilder->reveal()) {
            use DataProviderRepositoryTrait;

            public function __construct(
                AccessControlQueryEnhancer $accessControlQueryEnhancer,
                private QueryBuilder $queryBuilder,
            ) {
                $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
            }

            /**
             * @param string $alias
             * @param string|null $indexBy
             */
            public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
            {
                return $this->queryBuilder;
            }

            /**
             * @param string $alias
             * @param string $locale
             */
            public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale): void
            {
            }
        };

        $findByFiltersIdsReflection = new \ReflectionMethod(
            \get_class($dataProviderRepositoryTrait),
            'findByFiltersIds'
        );
        $findByFiltersIdsReflection->setAccessible(true);

        $findByFiltersIdsReflection->invoke(
            $dataProviderRepositoryTrait,
            [],
            1,
            5,
            null,
            'de',
            [],
            $user->reveal(),
            'Some\\Entity',
            'entity',
            64
        );

        $accessControlQueryEnhancer
            ->enhance($queryBuilder->reveal(), $user->reveal(), 64, 'Some\\Entity', 'entity')
            ->shouldBeCalled();
    }

    public function testFindByFiltersIdsWithDatasourceWithoutIncludeSubFolders(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);

        $this->queryBuilder->select(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->distinct(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->getQuery()->willReturn($query);

        $this->findByFiltersIds(
            filters: ['dataSource' => 3],
            page: 1,
            pageSize: 5,
            limit: null,
            locale: 'de',
        );

        // using distinct here is essential, since due to our joins multiple rows might be returned
        // this makes problems if also a limit is used
        $this->queryBuilder->distinct()->shouldBeCalled();
    }

    public function testFindByFiltersWithSorting(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setParameter(Argument::cetera())->willReturn($query);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $query->getResult()->willReturn([]);

        $this->queryBuilder->addSelect(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->select(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->where(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->distinct(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->getAllAliases()->willReturn([]);
        $this->queryBuilder->getQuery()->willReturn($query);
        $this->queryBuilder->orderBy('entity.test', 'asc')->shouldBeCalled();

        $this->findByFilters(
            filters: ['sortBy' => 'test', 'sortMethod' => 'asc'],
            page: 1,
            pageSize: 5,
            limit: null,
            locale: 'de'
        );
    }

    public function testFindByFiltersWithoutSorting(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setParameter(Argument::cetera())->willReturn($query);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $query->getResult()->willReturn([]);

        $this->queryBuilder->addSelect(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->select(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->where(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->orderBy(Argument::cetera())->willReturn($this->queryBuilder);
        $this->queryBuilder->distinct(Argument::cetera())->willReturn($this->queryBuilder);

        $this->queryBuilder->getAllAliases()->willReturn([]);
        $this->queryBuilder->getQuery()->willReturn($query);
        $this->queryBuilder->orderBy(null, 'asc')->shouldNotBeCalled();

        $this->findByFilters(
            filters: ['sortBy' => null, 'sortMethod' => 'asc'],
            page: 1,
            pageSize: 5,
            limit: null,
            locale: 'de'
        );
    }
}
