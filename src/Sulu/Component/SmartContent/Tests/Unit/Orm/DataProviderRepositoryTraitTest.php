<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Tests\Orm;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;

class Query extends AbstractQuery
{
    public function setFirstResult()
    {
    }

    public function setMaxResults()
    {
    }

    public function getSQL()
    {
    }

    public function _doExecute()
    {
    }
}

class DataProviderRepositoryTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DataProviderRepositoryTrait
     */
    private $dataProviderRepositoryTrait;

    public function setUp()
    {
        $this->dataProviderRepositoryTrait = $this->getMockForTrait(DataProviderRepositoryTrait::class);
    }

    public function testFindByFiltersIds()
    {
        $findByFiltersIdsReflection = new \ReflectionMethod(
            get_class($this->dataProviderRepositoryTrait),
            'findByFiltersIds'
        );
        $findByFiltersIdsReflection->setAccessible(true);

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('setMaxResults')->willReturn($query);
        $query->method('getScalarResult')->willReturn([]);
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
}
