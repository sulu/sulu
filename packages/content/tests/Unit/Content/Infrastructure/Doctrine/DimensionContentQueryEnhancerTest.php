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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

class DimensionContentQueryEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateQuerySetsRefreshHintWhenDimensionContentJoined(): void
    {
        $enhancer = new DimensionContentQueryEnhancer();

        $query = $this->prophesize(Query::class);
        $query->setHint(Query::HINT_REFRESH, true)->willReturn($query)->shouldBeCalled();

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getQuery()->willReturn($query->reveal());
        $queryBuilder->getAllAliases()->willReturn(['example', 'dimensionContent']);

        $result = $enhancer->createQuery($queryBuilder->reveal());

        $this->assertSame($query->reveal(), $result);
    }

    public function testCreateQueryDoesNotSetRefreshHintWithoutDimensionContentJoin(): void
    {
        $enhancer = new DimensionContentQueryEnhancer();

        $query = $this->prophesize(Query::class);
        $query->setHint(Query::HINT_REFRESH, true)->shouldNotBeCalled();

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->getQuery()->willReturn($query->reveal());
        $queryBuilder->getAllAliases()->willReturn(['example', 'filterDimensionContent']);

        $result = $enhancer->createQuery($queryBuilder->reveal());

        $this->assertSame($query->reveal(), $result);
    }
}
