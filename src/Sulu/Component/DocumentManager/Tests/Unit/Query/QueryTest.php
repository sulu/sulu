<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\tests\Unit\Query;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->phpcrQuery = $this->prophesize(QueryInterface::class);
        $this->phpcrResult = $this->prophesize(QueryResultInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $this->query = new Query(
            $this->phpcrQuery->reveal(),
            $this->dispatcher->reveal(),
            'fr',
            [],
            'p'
        );
    }

    /**
     * It should be able to return PHPCR results.
     */
    public function testExecutePhpcr()
    {
        $parameters = [
            'one' => 'two',
            'three' => 'four',
        ];
        $limit = 10;
        $firstResult = 5;

        $this->phpcrQuery->setLimit($limit)->shouldBeCalled();
        $this->phpcrQuery->setOffset($firstResult)->shouldBeCalled();

        foreach ($parameters as $key => $value) {
            $this->phpcrQuery->bindValue($key, $value)->shouldBeCalled();
        }

        $this->phpcrQuery->execute()->willReturn($this->phpcrResult->reveal());

        $this->query->setMaxResults($limit);
        $this->query->setFirstResult($firstResult);

        $result = $this->query->execute($parameters, Query::HYDRATE_PHPCR);

        $this->assertSame($this->phpcrResult->reveal(), $result);
    }

    /**
     * It should return documents by default.
     */
    public function testExecuteDocument()
    {
        $resultCollection = $this->prophesize(QueryResultCollection::class);
        $this->dispatcher->dispatch(Events::QUERY_EXECUTE, new QueryExecuteEvent($this->query))->will(function ($args) use ($resultCollection) {
            $args[1]->setResult($resultCollection->reveal());
        });

        $result = $this->query->execute();
        $this->assertSame($resultCollection->reveal(), $result);
    }
}
