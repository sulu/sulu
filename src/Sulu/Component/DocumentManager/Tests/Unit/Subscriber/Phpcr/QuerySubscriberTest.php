<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\QuerySubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Query\Query;
use PHPCR\Query\QueryResultInterface;
use Sulu\Component\DocumentManager\Query\ResultCollection;

class QuerySubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->session = $this->prophesize(SessionInterface::class);
        $this->workspace = $this->prophesize(WorkspaceInterface::class);
        $this->queryManager = $this->prophesize(QueryManagerInterface::class);
        $this->dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->phpcrQuery = $this->prophesize(QueryInterface::class);
        $this->phpcrResult = $this->prophesize(QueryResultInterface::class);
        $this->queryCreateEvent = $this->prophesize(QueryCreateEvent::class);
        $this->queryExecuteEvent = $this->prophesize(QueryExecuteEvent::class);
        $this->query = $this->prophesize(Query::class);

        $this->subscriber = new QuerySubscriber(
            $this->session->reveal(),
            $this->dispatcher->reveal()
        );

        $this->session->getWorkspace()->willReturn($this->workspace->reveal());
        $this->workspace->getQueryManager()->willReturn($this->queryManager->reveal());
    }

    /**
     * It should provide a Query object
     */
    public function testHandleCreate()
    {
        $query = 'SELECT * FROM [nt:unstructured]';
        $locale = 'fr';
        $primarySelector = 'p';

        $this->queryCreateEvent->getQueryString()->willReturn($query);
        $this->queryCreateEvent->getLocale()->willReturn($locale);
        $this->queryCreateEvent->getPrimarySelector()->willReturn($primarySelector);
        $this->queryManager->createQuery($query, 'JCR-SQL2')->willReturn($this->phpcrQuery->reveal());
        $this->queryCreateEvent->setQuery(new Query(
            $this->phpcrQuery->reveal(),
            $this->dispatcher->reveal(),
            $locale,
            $primarySelector
        ))->shouldBeCalled();

        $this->subscriber->handleCreate($this->queryCreateEvent->reveal());
    }

    /**
     * It should handle query execution and set the result
     */
    public function testHandleQueryExecute()
    {
        $locale = 'fr';
        $primarySelector = 'p';

        $this->query->getLocale()->willReturn($locale);
        $this->query->getPhpcrQuery()->willReturn($this->phpcrQuery->reveal());
        $this->phpcrQuery->execute()->willReturn($this->phpcrResult->reveal());
        $this->query->getPrimarySelector()->willReturn($primarySelector);
        $this->queryExecuteEvent->getQuery()->willReturn($this->query->reveal());

        $this->queryExecuteEvent->setResult(
            new ResultCollection(
                $this->phpcrResult->reveal(),
                $this->dispatcher->reveal(),
                $locale
            )
        )->shouldBeCalled();

        $this->subscriber->handleQueryExecute($this->queryExecuteEvent->reveal());
    }
}

