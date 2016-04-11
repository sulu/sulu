<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Unit\EventSubscriber\ORM;

use Doctrine\ORM\QueryBuilder;
use Sulu\Component\Persistence\Repository\ORM\OrderByTrait;

class OrderByTraitTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var OrderByTrait
     */
    private $orderByTrait;

    /**
     * @var \ReflectionMethod
     */
    private $addOrderByFunction;

    public function setUp()
    {
        parent::setUp();

        $this->orderByTrait = $this->getObjectForTrait(OrderByTrait::class);

        $this->queryBuilder = $this->prophesize(QueryBuilder::class);

        $reflectionClass = new \ReflectionClass($this->orderByTrait);
        $this->addOrderByFunction = $reflectionClass->getMethod('addOrderBy');
        $this->addOrderByFunction->setAccessible(true);
    }

    public function orderByProvider()
    {
        return [
            ['user', ['firstName' => 'ASC'], ['user.firstName' => 'ASC']],
            ['user', ['firstName' => 'ASC', 'test.a' => 'DESC'], ['user.firstName' => 'ASC', 'test.a' => 'DESC']],
            ['u', ['test.a' => 'DESC'], ['test.a' => 'DESC']],
            ['u', [], []],
        ];
    }

    /**
     * @dataProvider orderByProvider
     */
    public function testAddOrderBy($alias, $orderBy, $expectedOrderBy)
    {
        foreach ($expectedOrderBy as $field => $order) {
            $this->queryBuilder->addOrderBy($field, $order)->shouldBeCalledTimes(1);
        }

        $this->addOrderByFunction->invokeArgs(
            $this->orderByTrait,
            [
                $this->queryBuilder->reveal(),
                $alias,
                $orderBy,
            ]
        );
    }
}
