<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\LocalizedSearchManager;

use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;

/**
 * Provides a localized search-manager interface‚
 * @package Sulu\Bundle\SearchBundle\SearchManager
 */
class LocalizedSearchManager implements LocalizedSearchManagerInterface
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    function __construct(SearchManagerInterface $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    /**
     * Attempt to index the given object
     *
     * @param object $object
     * @param string $locale
     */
    public function index($object, $locale)
    {
        $metadata = $this->searchManager->getMetadata($object);

        $this->searchManager->indexWithMetadata($object, new LocalizedMetadata($metadata, $locale));
    }

    /**
     * Search with the given query string
     *
     * @param string $string
     * @param string $locale
     * @param array|string $indexNames
     * @return \Massive\Bundle\SearchBundle\Search\QueryHit[]
     */
    public function search($string, $locale, $indexNames = null)
    {
        // append locale to index
        for ($i = 0, $len = sizeof($indexNames); $i < $len; $i++) {
            $indexNames[$i] .= '_' . $locale;
        }

        return $this->searchManager->search($string, $indexNames);
    }
} 
