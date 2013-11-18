<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

/**
 * Defines the methods for the PortalManager
 */
interface PortalManagerInterface
{
    /**
     * Returns the portal with the given key
     * @param $key The key to search for
     * @return Portal
     */
    public function findByKey($key);

    /**
     * Returns the portal with the given url (which has not necessarily to be the main url)
     * @param $searchUrl The url to search for
     * @return Portal
     */
    public function findByUrl($searchUrl);

    /**
     * Returns all the portals managed by the specific instance
     * @return PortalCollection
     */
    public function getPortals();

    /**
     * Sets the current portal (valid for this request)
     * @param Portal $portal The current portal
     */
    public function setCurrentPortal(Portal $portal);

    /**
     * Returns the current portal for this request
     * @return Portal
     */
    public function getCurrentPortal();
}
