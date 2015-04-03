<?php

namespace Sulu\Component\DocumentManager\Tests\Unit;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Sulu\Component\DocumentManager\NodeManager;
use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\RemoveEvent;
use Sulu\Component\DocumentManager\Event\CopyEvent;
use Sulu\Component\DocumentManager\Event\MoveEvent;
use Sulu\Component\DocumentManager\Event\CreateEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Event\FindEvent;
use Sulu\Component\DocumentManager\Query\Query;
use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Query\ResultCollection;

class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dispatcher = new EventDispatcher();
        $this->nodeManager = $this->prophesize(NodeManager::class);
        $this->manager = new DocumentManager(
            $this->dispatcher,
            $this->nodeManager->reveal()
        );

        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new \stdClass();

        $this->query = $this->prophesize(Query::class);
        $this->resultCollection = $this->prophesize(ResultCollection::class);
    }

    /**
     * It should issue a persist event for the passed document
     */
    public function testPersist()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->persist(new \stdClass(), 'fr');
        $this->assertTrue($subscriber->persist);
    }

    /**
     * It should issue a remove event
     */
    public function testRemove()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->remove(new \stdClass());
        $this->assertTrue($subscriber->remove);
    }

    /**
     * It should issue a move event
     */
    public function testMove()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->move(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->move);
    }

    /**
     * It should issue a copy event
     */
    public function testCopy()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->copy(new \stdClass(), '/path/to');
        $this->assertTrue($subscriber->copy);
    }

    /**
     * It should issue a create event
     */
    public function testCreate()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->create('foo');
        $this->assertTrue($subscriber->create);
    }

    /**
     * It should issue a clear event
     */
    public function testClear()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->clear();
        $this->assertTrue($subscriber->clear);
    }

    /**
     * It should issue a flush event
     */
    public function testFlush()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->flush();
        $this->assertTrue($subscriber->flush);
    }

    /**
     * It should issue a find event
     */
    public function testFind()
    {
        $subscriber = $this->addSubscriber();
        $this->manager->find('foo', 'fr');
        $this->assertTrue($subscriber->find);
    }

    /**
     * It should issue a query create event
     */
    public function testQueryCreate()
    {
        $subscriber = $this->addSubscriber();
        $query = $this->manager->createQuery('SELECT foo FROM [foo:bar]', 'fr');
        $this->assertTrue($subscriber->queryCreate);
        $this->assertInstanceOf(Query::class, $query);
    }

    /**
     * It should issue a query builder create event
     *
     * NOT SUPPORTED
     */
    public function testQueryCreateBuilder()
    {
        $this->markTestSkipped('Not supported yet');
    }

    /**
     * It should issue a query execute PHPCR event
     */
    public function testQueryExecute()
    {
        $subscriber = $this->addSubscriber();
        $query = $this->prophesize(QueryInterface::class);
        $this->manager->getDocumentsByPhpcrQuery($query->reveal());
        $this->assertTrue($subscriber->queryExecute);
    }

    private function addSubscriber()
    {
        $subscriber = new TestDocumentManagerSubscriber($this->query->reveal(), $this->resultCollection->reveal());
        $this->dispatcher->addSubscriber($subscriber);

        return $subscriber;
    }
}

class TestDocumentManagerSubscriber implements EventSubscriberInterface
{
    public $persist = false;
    public $hydrate = false;
    public $remove = false;
    public $copy = false;
    public $move = false;
    public $create = false;
    public $clear = false;
    public $flush = false;
    public $find = false;
    public $queryCreate = false;
    public $queryCreateBuilder = false;
    public $queryExecute = false;

    private $query;
    private $resultCollection;

    public function __construct(Query $query, ResultCollection $resultCollection)
    {
        $this->query = $query;
        $this->resultCollection = $resultCollection;
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::PERSIST => 'handlePersist',
            Events::REMOVE => 'handleRemove',
            Events::MOVE => 'handleMove',
            Events::COPY => 'handleCopy',
            Events::CREATE => 'handleCreate',
            Events::CLEAR => 'handleClear',
            Events::FLUSH => 'handleFlush',
            Events::FIND => 'handleFind',
            Events::QUERY_CREATE => 'handleQueryCreate',
            Events::QUERY_CREATE_BUILDER => 'handleQueryBuilderCreate',
            Events::QUERY_EXECUTE => 'handleQueryExecute',
        );
    }

    public function handlePersist(PersistEvent $event)
    {
        $this->persist = true;
    }

    public function handleRemove(RemoveEvent $event)
    {
        $this->remove = true;
    }

    public function handleCopy(CopyEvent $event)
    {
        $this->copy = true;
    }

    public function handleMove(MoveEvent $event)
    {
        $this->move = true;
    }

    public function handleCreate(CreateEvent $event)
    {
        $this->create = true;
        $event->setDocument(new \stdClass());
    }

    public function handleClear(ClearEvent $event)
    {
        $this->clear = true;
    }

    public function handleFlush(FlushEvent $event)
    {
        $this->flush = true;
    }

    public function handleFind(FindEvent $event)
    {
        $this->find = true;
        $event->setDocument(new \stdClass());
    }

    public function handleQueryCreate(QueryCreateEvent $event)
    {
        $this->queryCreate = true;
        $event->setQuery($this->query);
    }

    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $this->queryExecute = true;
        $event->setResult($this->resultCollection);
    }
}
