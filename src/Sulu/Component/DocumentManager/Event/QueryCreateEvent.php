<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\DocumentManager\Query\Query;

class QueryCreateEvent extends AbstractEvent
{
    use EventOptionsTrait;

    /**
     * @var string
     */
    private $innerQuery;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var null|string
     */
    private $primarySelector;

    /**
     * @param string $innerQuery
     * @param string $locale
     * @param array $options
     * @param null|string $primarySelector
     */
    public function __construct($innerQuery, $locale, array $options = [], $primarySelector = null)
    {
        $this->innerQuery = $innerQuery;
        $this->locale = $locale;
        $this->options = $options;
        $this->primarySelector = $primarySelector;
    }

    /**
     * @return string
     */
    public function getInnerQuery()
    {
        return $this->innerQuery;
    }

    /**
     * @param Query $query
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return null|string
     */
    public function getPrimarySelector()
    {
        return $this->primarySelector;
    }

    /**
     * @return mixed
     *
     * @throws DocumentManagerException
     */
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
