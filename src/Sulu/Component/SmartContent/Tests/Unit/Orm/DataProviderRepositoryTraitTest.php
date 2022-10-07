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
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class Query extends AbstractQuery
{
    /**
     * @return void
     */
    public function setFirstResult()
    {
    }

    /**
     * @return void
     */
    public function setMaxResults()
    {
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

    /**
     * @var DataProviderRepositoryTrait
     */
    private $dataProviderRepositoryTrait;

    public function setUp(): void
    {
        $this->dataProviderRepositoryTrait = $this->getMockForTrait(DataProviderRepositoryTrait::class);
    }

    public function testFindByFiltersIds(): void
    {
        $findByFiltersIdsReflection = new \ReflectionMethod(
            \get_class($this->dataProviderRepositoryTrait),
            'findByFiltersIds'
        );
        $findByFiltersIdsReflection->setAccessible(true);

        $query = $this->prophesize(Query::class);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->distinct(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->orderBy(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query);

        $this->dataProviderRepositoryTrait->method('createQueryBuilder')->willReturn($queryBuilder->reveal());

        $findByFiltersIdsReflection->invoke($this->dataProviderRepositoryTrait, [], 1, 5, null, 'de');

        // using distinct here is essential, since due to our joins multiple rows might be returned
        // this makes problems if also a limit is used
        $queryBuilder->distinct()->shouldBeCalled();
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

            /**
             * @var QueryBuilder
             */
            private $queryBuilder;

            public function __construct(AccessControlQueryEnhancer $accessControlQueryEnhancer, QueryBuilder $queryBuilder)
            {
                $this->accessControlQueryEnhancer = $accessControlQueryEnhancer;
                $this->queryBuilder = $queryBuilder;
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

        $accessControlQueryEnhancer->enhance($queryBuilder->reveal(), $user->reveal(), 64, 'Some\\Entity', 'entity')
            ->shouldBeCalled();
    }

    public function testFindByFiltersIdsWithDatasourceWithoutIncludeSubFolders(): void
    {
        $findByFiltersIdsReflection = new \ReflectionMethod(
            \get_class($this->dataProviderRepositoryTrait),
            'findByFiltersIds'
        );
        $findByFiltersIdsReflection->setAccessible(true);

        $query = $this->prophesize(Query::class);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->distinct(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->orderBy(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query);

        $this->dataProviderRepositoryTrait->method('createQueryBuilder')->willReturn($queryBuilder->reveal());

        $findByFiltersIdsReflection->invoke($this->dataProviderRepositoryTrait, ['dataSource' => 3], 1, 5, null, 'de');

        // using distinct here is essential, since due to our joins multiple rows might be returned
        // this makes problems if also a limit is used
        $queryBuilder->distinct()->shouldBeCalled();
    }

    public function testFindByFiltersWithSorting(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setParameter(Argument::cetera())->willReturn($query);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $query->getResult()->willReturn([]);
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->orderBy(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->distinct(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->getAllAliases()->willReturn([]);
        $queryBuilder->getQuery()->willReturn($query);
        $this->dataProviderRepositoryTrait->method('createQueryBuilder')->willReturn($queryBuilder->reveal());
        $this->dataProviderRepositoryTrait->findByFilters(
            ['sortBy' => 'test', 'sortMethod' => 'asc'],
            1,
            5,
            null,
            'de'
        );

        $queryBuilder->orderBy('entity.test', 'asc')->shouldBeCalled();
    }

    public function testFindByFiltersWithoutSorting(): void
    {
        $query = $this->prophesize(Query::class);
        $query->setParameter(Argument::cetera())->willReturn($query);
        $query->setFirstResult(0)->willReturn($query);
        $query->setMaxResults(Argument::any())->willReturn($query);
        $query->getScalarResult()->willReturn([]);
        $query->getResult()->willReturn([]);
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->select(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->where(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->orderBy(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->distinct(Argument::cetera())->willReturn($queryBuilder);
        $queryBuilder->getAllAliases()->willReturn([]);
        $queryBuilder->getQuery()->willReturn($query);
        $this->dataProviderRepositoryTrait->method('createQueryBuilder')->willReturn($queryBuilder->reveal());
        $this->dataProviderRepositoryTrait->findByFilters(
            ['sortBy' => null, 'sortMethod' => 'asc'],
            1,
            5,
            null,
            'de'
        );

        $queryBuilder->orderBy(null, 'asc')->shouldNotBeCalled();
    }
}
