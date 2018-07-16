<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Query;

use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Based heavily on the PHPCR-ODM Query object.
 *
 * If we can break the phpcrQuery builder from the PHPCR-ODM we should
 * also be able to break-out the PhpcrQuery object too:
 *
 * https://github.com/doctrine/phpcr-odm/issues/627
 */
class Query
{
    const HYDRATE_DOCUMENT = 'document';

    const HYDRATE_PHPCR = 'phpcr_node';

    /**
     * @var QueryInterface
     */
    private $phpcrQuery;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var null|string
     */
    private $primarySelector;

    /**
     * @var null|string
     */
    private $locale;

    /**
     * @var array
     */
    private $options;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @param QueryInterface $phpcrQuery
     * @param EventDispatcherInterface $dispatcher
     * @param null|string $locale
     * @param array $options
     * @param null|string $primarySelector
     */
    public function __construct(
        QueryInterface $phpcrQuery,
        EventDispatcherInterface $dispatcher,
        $locale = null,
        array $options = [],
        $primarySelector = null
    ) {
        $this->phpcrQuery = $phpcrQuery;
        $this->dispatcher = $dispatcher;
        $this->locale = $locale;
        $this->options = $options;
        $this->primarySelector = $primarySelector;
    }

    /**
     * @param array $parameters
     * @param string $hydrationMode
     *
     * @return mixed|\PHPCR\Query\QueryResultInterface
     *
     * @throws DocumentManagerException
     */
    public function execute(array $parameters = [], $hydrationMode = self::HYDRATE_DOCUMENT)
    {
        if (null !== $this->maxResults) {
            $this->phpcrQuery->setLimit($this->maxResults);
        }

        if (null !== $this->firstResult) {
            $this->phpcrQuery->setOffset($this->firstResult);
        }

        foreach ($parameters as $key => $value) {
            $this->phpcrQuery->bindValue($key, $value);
        }

        if (self::HYDRATE_PHPCR === $hydrationMode) {
            return $this->phpcrQuery->execute();
        }

        if (self::HYDRATE_DOCUMENT !== $hydrationMode) {
            throw new DocumentManagerException(sprintf(
                'Unknown hydration mode "%s", should be either "document" or "phpcr_node"',
                $hydrationMode
            ));
        }

        $event = new QueryExecuteEvent($this, $this->options);
        $this->dispatcher->dispatch(Events::QUERY_EXECUTE, $event);

        return $event->getResult();
    }

    /**
     * @return int
     */
    public function getMaxResults()
    {
        return $this->maxResults;
    }

    /**
     * @param int $maxResults
     */
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    /**
     * @return int
     */
    public function getFirstResult()
    {
        return $this->firstResult;
    }

    /**
     * @param int $firstResult
     */
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    /**
     * @return null|string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return null|string
     */
    public function getPrimarySelector()
    {
        return $this->primarySelector;
    }

    /**
     * @param string $primarySelector
     */
    public function setPrimarySelector($primarySelector)
    {
        $this->primarySelector = $primarySelector;
    }

    /**
     * @return QueryInterface
     */
    public function getPhpcrQuery()
    {
        return $this->phpcrQuery;
    }
}
