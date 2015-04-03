<?php

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Query;

use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Query\Query;
use PHPCR\Query\QueryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\Query\ResultCollection;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;

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
            'p'
        );
    }

    /**
     * It should be able to return PHPCR results
     */
    public function testExecutePhpcr()
    {
        $parameters = array(
            'one' => 'two',
            'three' => 'four',
        );
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
     * It should return documents by default
     */
    public function testExecuteDocument()
    {
        $resultCollection = $this->prophesize(ResultCollection::class);
        $this->dispatcher->dispatch(Events::QUERY_EXECUTE, new QueryExecuteEvent($this->query))->will(function ($args) use ($resultCollection) {
            $args[1]->setResult($resultCollection->reveal());
        });

        $result = $this->query->execute();
        $this->assertSame($resultCollection->reveal(), $result);
    }
}
