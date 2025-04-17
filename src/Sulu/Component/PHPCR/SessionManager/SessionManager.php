<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionManager;

use PHPCR\SessionInterface;

class SessionManager implements SessionManagerInterface
{
    /**
     * @param string[] $nodeNames
     */
    public function __construct(
        private SessionInterface $session,
        private $nodeNames
    ) {
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getRouteNode($webspaceKey, $languageCode, $segment = null)
    {
        return $this->getSession()->getNode($this->getRoutePath($webspaceKey, $languageCode, $segment));
    }

    public function getRoutePath($webspaceKey, $languageCode, $segment = null)
    {
        $path = \sprintf(
            '/%s/%s/%s/%s%s',
            $this->nodeNames['base'],
            $webspaceKey,
            $this->nodeNames['route'],
            $languageCode,
            null !== $segment ? '/' . $segment : ''
        );

        return $path;
    }

    public function getContentNode($webspaceKey)
    {
        return $this->getSession()->getNode($this->getContentPath($webspaceKey));
    }

    public function getContentPath($webspaceKey)
    {
        $path = \sprintf(
            '/%s/%s/%s',
            $this->nodeNames['base'],
            $webspaceKey,
            $this->nodeNames['content']
        );

        return $path;
    }

    public function getWebspaceNode($webspaceKey)
    {
        return $this->getSession()->getNode($this->getWebspacePath($webspaceKey));
    }

    public function getWebspacePath($webspaceKey)
    {
        return \sprintf(
            '/%s/%s',
            $this->nodeNames['base'],
            $webspaceKey
        );
    }
}
