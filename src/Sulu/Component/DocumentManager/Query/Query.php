<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
 
namespace Sulu\Component\DocumentManager\Query;

use PHPCR\Query\QueryInterface;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Query\ResultCollection;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Sulu\Component\DocumentManager\Event\QueryExecuteEvent;
use Sulu\Component\DocumentManager\Events;

/**
 * Based heavily on the PHPCR-ODM Query object
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

    private $hydrationMode;
    private $locale;
    private $dispatcher;
    private $primarySelector;
    private $phpcrQuery;
    private $maxResults;
    private $firstResult;

    public function __construct(
        QueryInterface $phpcrQuery,
        EventDispatcherInterface $dispatcher, 
        $locale = null,
        $primarySelector = null
    )
    {
        $this->phpcrQuery = $phpcrQuery;
        $this->primarySelector = $primarySelector;
        $this->dispatcher = $dispatcher;
        $this->locale = $locale;
    }

    public function execute(array $parameters = array(), $hydrationMode = self::HYDRATE_DOCUMENT)
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

        if ($hydrationMode === self::HYDRATE_PHPCR) {
            return $this->phpcrQuery->execute();
        }

        if ($hydrationMode !== self::HYDRATE_DOCUMENT) {
            throw new DocumentManagerException(sprintf(
                'Unknown hydration mode "%s", should be either "document" or "phpcr_node"',
                $hydrationMode
            ));
        }

        $event = new QueryExecuteEvent($this);
        $this->dispatcher->dispatch(Events::QUERY_EXECUTE, $event);

        return $event->getResult();
    }

    public function getMaxResults() 
    {
        return $this->maxResults;
    }
    
    public function setMaxResults($maxResults)
    {
        $this->maxResults = $maxResults;
    }

    public function getFirstResult() 
    {
        return $this->firstResult;
    }
    
    public function setFirstResult($firstResult)
    {
        $this->firstResult = $firstResult;
    }

    public function getHydrationMode() 
    {
        return $this->hydrationMode;
    }
    
    public function setHydrationMode($hydrationMode)
    {
        $this->hydrationMode = $hydrationMode;
    }

    public function getLocale() 
    {
        return $this->locale;
    }
    
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    public function getPrimarySelector() 
    {
        return $this->primarySelector;
    }
    
    public function setPrimarySelector($primarySelector)
    {
        $this->primarySelector = $primarySelector;
    }

    public function getPhpcrQuery() 
    {
        return $this->phpcrQuery;
    }
    
}
