<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Comonent\DocumentManager\Tests\Unit\Subscriber;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\SessionInterface;
use PHPCR\WorkspaceInterface;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Query\Query;
use Sulu\Component\DocumentManager\Subscriber\Phpcr\QuerySubscriber;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class QuerySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var WorkspaceInterface
     */
    private $workspace;

    /**
     * @var QueryManagerInterface
     */
    private $queryManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var QueryInterface
     */
    private $phpcrQuery;

    /**
     * @var QueryResultInterface
     */
    private $phpcrResult;

    /**
     * @var QueryCreateEvent
     */
    private $queryCreateEvent;

    /**
     * @var QueryExecuteEvent
     */
    private $queryExecuteEvent;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var QuerySubscriber
     */
    private $subscriber;

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
     * It should provide a Query object from a JCR-SQL2 string.
     */
    public function testHandleCreate()
    {
        $query = 'SELECT * FROM [nt:unstructured]';
        $locale = 'fr';
        $primarySelector = 'p';

        $this->queryCreateEvent->getInnerQuery()->willReturn($query);
        $this->queryCreateEvent->getLocale()->willReturn($locale);
        $this->queryCreateEvent->getOptions()->willReturn([]);
        $this->queryCreateEvent->getPrimarySelector()->willReturn($primarySelector);
        $this->queryManager->createQuery($query, 'JCR-SQL2')->willReturn($this->phpcrQuery->reveal());
        $this->queryCreateEvent->setQuery(new Query(
            $this->phpcrQuery->reveal(),
            $this->dispatcher->reveal(),
            $locale,
            [],
            $primarySelector
        ))->shouldBeCalled();

        $this->subscriber->handleCreate($this->queryCreateEvent->reveal());
    }

    /**
     * It should provide a Query object for a PHPCR query object.
     */
    public function testHandleCreateFromPhpcrQuery()
    {
        $locale = 'fr';
        $primarySelector = 'p';

        $this->queryCreateEvent->getInnerQuery()->willReturn($this->phpcrQuery->reveal());
        $this->queryCreateEvent->getLocale()->willReturn($locale);
        $this->queryCreateEvent->getOptions()->willReturn([]);
        $this->queryCreateEvent->getPrimarySelector()->willReturn($primarySelector);
        $this->queryManager->createQuery($this->phpcrQuery->reveal(), 'JCR-SQL2')->willReturn($this->phpcrQuery->reveal());
        $this->queryCreateEvent->setQuery(new Query(
            $this->phpcrQuery->reveal(),
            $this->dispatcher->reveal(),
            $locale,
            [],
            $primarySelector
        ))->shouldBeCalled();

        $this->subscriber->handleCreate($this->queryCreateEvent->reveal());
    }

    /**
     * It should handle query execution and set the result.
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
        $this->queryExecuteEvent->getOptions()->willReturn([]);

        $this->queryExecuteEvent->setResult(
            new QueryResultCollection(
                $this->phpcrResult->reveal(),
                $this->dispatcher->reveal(),
                $locale
            )
        )->shouldBeCalled();

        $this->subscriber->handleQueryExecute($this->queryExecuteEvent->reveal());
    }
}
