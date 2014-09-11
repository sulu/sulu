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

/**
 * Provides a localized search-manager interface‚
 * @package Sulu\Bundle\SearchBundle\SearchManager
 */
interface LocalizedSearchManagerInterface
{
    /**
     * Search with the given query string
     *
     * @param string $string
     * @param string $locale
     * @param array|string $indexNames
     * @return \Massive\Bundle\SearchBundle\Search\QueryHit[]
     */
    public function search($string, $locale, $indexNames = null);

    /**
     * Attempt to index the given object
     *
     * @param object $object
     * @param string $locale
     */
    public function index($object, $locale);
}
