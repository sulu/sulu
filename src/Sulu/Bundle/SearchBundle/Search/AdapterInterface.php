<?php

namespace Sulu\Bundle\SearchBundle\Search;

interface AdapterInterface
{
    /**
     * Index the given IndexEntry object
     *
     * @param IndexEntry $indexEntry
     */
    public function index(Document $document, $indexName);

    /**
     * Search using the given query string
     *
     * @param string $queryString
     */
    public function search($queryString, array $indexNames = array());
}
