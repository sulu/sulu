<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Preview;

use Sulu\Component\Content\StructureInterface;

interface PreviewCacheProviderInterface
{

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     * @param string $webspaceKey The key of webspace.
     * @param string $locale locale to load temporary data.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    function fetch($id, $webspaceKey, $locale);

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     * @param string $webspaceKey The key of webspace.
     * @param string $locale The current locale.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    function contains($id, $webspaceKey, $locale);

    /**
     * Puts data into the cache.
     *
     * @param string $id The cache id.
     * @param mixed $data The cache entry/data.
     * @param string $webspaceKey The key of webspace.
     * @param string $locale The locale to save data.
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    function save($id, $data, $webspaceKey, $locale);

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     * @param string $webspaceKey The key of webspace.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    function delete($id, $webspaceKey);
}
