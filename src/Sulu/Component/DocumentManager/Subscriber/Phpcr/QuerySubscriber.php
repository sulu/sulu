<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Phpcr;

use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\SessionInterface;
use Sulu\Component\DocumentManager\Collection\QueryResultCollection;
use Sulu\Component\DocumentManager\Event\QueryCreateBuilderEvent;
use Sulu\Component\DocumentManager\Event\QueryCreateEvent;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Query\Query;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles creation of query and query builder objects.
 */
class QuerySubscriber implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param SessionInterface $session
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(SessionInterface $session, EventDispatcherInterface $eventDispatcher)
    {
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::QUERY_CREATE => ['handleCreate', 500],
            Events::QUERY_CREATE_BUILDER => ['handleCreateBuilder', 500],
            Events::QUERY_EXECUTE => ['handleQueryExecute', 500],
        ];
    }

    /**
     * Create a new Sulu Query object.
     *
     * @param QueryCreateEvent $event
     */
    public function handleCreate(QueryCreateEvent $event)
    {
        $innerQuery = $event->getInnerQuery();

        if (is_string($innerQuery)) {
            $phpcrQuery = $this->getQueryManager()->createQuery($innerQuery, QueryInterface::JCR_SQL2);
        } elseif ($innerQuery instanceof QueryInterface) {
            $phpcrQuery = $innerQuery;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Expected inner query to be either a string or a PHPCR query object, got: "%s"',
                is_object($innerQuery) ? get_class($innerQuery) : gettype($innerQuery)
            ));
        }

        $event->setQuery(
            new Query(
                $phpcrQuery,
                $this->eventDispatcher,
                $event->getLocale(),
                $event->getOptions(),
                $event->getPrimarySelector()
            )
        );
    }

    /**
     * TODO: We should reuse the PHPCR-ODM query builder here, see:
     *       https://github.com/doctrine/phpcr-odm/issues/627.
     *
     * @param QueryCreateBuilderEvent $event
     *
     * @throws \Exception
     */
    public function handleCreateBuilder(QueryCreateBuilderEvent $event)
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Handle query execution.
     *
     * @param QueryExecuteEvent $event
     */
    public function handleQueryExecute(QueryExecuteEvent $event)
    {
        $query = $event->getQuery();
        $locale = $query->getLocale();
        $phpcrResult = $query->getPhpcrQuery()->execute();

        $event->setResult(
            new QueryResultCollection($phpcrResult, $this->eventDispatcher, $locale, $event->getOptions())
        );
    }

    /**
     * @return QueryManagerInterface
     */
    private function getQueryManager()
    {
        return $this->session->getWorkspace()->getQueryManager();
    }
}
