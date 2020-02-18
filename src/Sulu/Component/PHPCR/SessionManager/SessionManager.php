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
     * @var string[]
     */
    private $nodeNames;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(SessionInterface $session, $nodeNames)
    {
        $this->session = $session;
        $this->nodeNames = $nodeNames;
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
        $path = sprintf(
            '/%s/%s/%s/%s%s',
            $this->nodeNames['base'],
            $webspaceKey,
            $this->nodeNames['route'],
            $languageCode,
            (null !== $segment ? '/' . $segment : '')
        );

        return $path;
    }

    public function getContentNode($webspaceKey)
    {
        return $this->getSession()->getNode($this->getContentPath($webspaceKey));
    }

    public function getContentPath($webspaceKey)
    {
        $path = sprintf(
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
        return sprintf(
            '/%s/%s',
            $this->nodeNames['base'],
            $webspaceKey
        );
    }

    public function getSnippetNode($templateKey = null)
    {
        $snippetPath = '/' . $this->nodeNames['base'] . '/' . $this->nodeNames['snippet'];
        $nodePath = $snippetPath . '/' . $templateKey;

        if (null === $templateKey) {
            $nodePath = $snippetPath;
        }

        try {
            $node = $this->getSession()->getNode($nodePath);
        } catch (\PHPCR\PathNotFoundException $e) {
            $node = $this->getSession()->getNode($snippetPath)->addNode($templateKey);
        }

        return $node;
    }
}
