<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Manager;

use Sulu\Component\Workspace\Portal;
use Sulu\Component\Workspace\Workspace;
use Sulu\Component\Workspace\WorkspaceCollection;

/**
 * Defines the methods for the WorkspaceManager
 */
interface WorkspaceManagerInterface
{
    /**
     * Returns the workspace with the given key
     * @param $key string The key to search for
     * @return Workspace
     */
    public function findWorkspaceByKey($key);

    /**
     * Returns the portal with the given key
     * @param string $key The key to search for
     * @return Portal
     */
    public function findPortalByKey($key);

    /**
     * Returns the portal with the given url (which has not necessarily to be the main url)
     * @param string $url The url to search for
     * @param string $environment The environment in which the url should be searched
     * @return Portal
     */
    public function findPortalInformationByUrl($url, $environment);

    /**
     * Returns all the workspaces managed by this specific instance
     * @return WorkspaceCollection
     */
    public function getWorkspaceCollection();
}
