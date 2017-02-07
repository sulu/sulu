<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionManager;

use PHPCR\NodeInterface;
use PHPCR\SessionInterface;

/**
 * Provides interface for session manager.
 */
interface SessionManagerInterface
{
    /**
     * Returns a valid session to interact with a phpcr database.
     *
     * @return SessionInterface
     *
     * @deprecated Use the doctrine_phpcr service to retrieve the session
     */
    public function getSession();

    /**
     * Returns the route node for given webspace.
     *
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segment
     *
     * @return NodeInterface
     *
     * @deprecated Do not use anymore, because the node is always returned from the default session, although multiple
     *             sessions can exist
     */
    public function getRouteNode($webspaceKey, $languageCode, $segment = null);

    /**
     * Returns the route path for given webspace.
     *
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segment
     *
     * @return string
     */
    public function getRoutePath($webspaceKey, $languageCode, $segment = null);

    /**
     * Returns the content node for given webspace.
     *
     * @param string $webspaceKey
     *
     * @return NodeInterface
     *
     * @deprecated Do not use anymore, because the node is always returned from the default session, although multiple
     *             sessions can exist
     */
    public function getContentNode($webspaceKey);

    /**
     * Returns the content path for given webspace.
     *
     * @param string $webspaceKey
     *
     * @return string
     */
    public function getContentPath($webspaceKey);

    /**
     * Returns the webspace node for given webspace.
     *
     * @param string$webspaceKey
     *
     * @return NodeInterface
     *
     * @deprecated Do not use anymore, because the node is always returned from the default session, although multiple
     *             sessions can exist
     */
    public function getWebspaceNode($webspaceKey);

    /**
     * Returns the webspace path for given webspace.
     *
     * @param string$webspaceKey
     *
     * @return string
     */
    public function getWebspacePath($webspaceKey);

    /**
     * returns the snippet node.
     *
     * @return \PHPCR\NodeInterface
     *
     * @deprecated Do not use anymore, because the node is always returned from the default session, although multiple
     *             sessions can exist
     */
    public function getSnippetNode();
}
