<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Symfony\Component\EventDispatcher\Event;
use Sulu\Component\DocumentManager\Query\Query;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class QueryCreateEvent extends Event
{
    private $queryString;
    private $query;
    private $locale;
    private $primarySelector;

    public function __construct($queryString, $locale, $primarySelector = null)
    {
        $this->queryString = $queryString;
        $this->locale = $locale;
        $this->primarySelector = $primarySelector;
    }

    public function getQueryString()
    {
        return $this->queryString;
    }

    public function setQuery(Query $query)
    {
        $this->query = $query;
    }

    public function getLocale() 
    {
        return $this->locale;
    }

    public function getPrimarySelector() 
    {
        return $this->primarySelector;
    }

    public function getQuery()
    {
        if (!$this->query) {
            throw new DocumentManagerException(
                'No query has been set in listener. A listener should have set the query'
            );
        }

        return $this->query;
    }
}
