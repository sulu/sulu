<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use PHPCR\Query\QueryManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use PHPCR\SessionInterface;
use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Query\ResultCollection;
use Sulu\Component\DocumentManager\Events;

/**
 * Handles creation of query and query builder objects
 */
class QuerySubscriber implements EventSubscriberInterface
{
    private $session;
    private $eventDispatcher;

    /**
     * @param SessionInterface $session
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(SessionInterface $session, EventDispatcherInterface $eventDispatcher)
    {
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::QUERY_CREATE => array('handleCreate', 500),
            Events::QUERY_CREATE_BUILDER => array('handleCreateBuilder', 500),
            Events::QUERY_EXECUTE => array('handleQueryExecute', 500),
        );
    }

    /**
     * Create a new Sulu Query object
     *
     * @param QueryCreateEvent $event
     */
    public function handleCreate(QueryCreateEvent $event)
    {
        $phpcrQuery = $this->getQueryManager()->createQuery($event->getQueryString(), QueryInterface::JCR_SQL2);

        $event->setQuery(new Query($phpcrQuery, $this->eventDispatcher, $event->getLocale(), $event->getPrimarySelector()));
    }

    /**
     * TODO: We should reuse the PHPCR-ODM query builder here, see:
     *       https://github.com/doctrine/phpcr-odm/issues/627
     *
     * @param QueryCreateBuilderEvent $event
     */
    public function handleCreateBuilder(QueryCreateBuilderEvent $event)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Handle query execution
     *
     * @param QueryExecuteEvent
     */
    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $query = $event->getQuery();
        $locale = $query->getLocale();
        $phpcrResult = $query->getPhpcrQuery()->execute();

        $event->setResult(new ResultCollection($phpcrResult, $this->eventDispatcher, $locale));
    }

    /**
     * @return QueryManagerInterface
     */
    private function getQueryManager()
    {
        return $this->session->getWorkspace()->getQueryManager();
    }
}
