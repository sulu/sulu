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

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteNode($webspaceKey, $languageCode, $segment = null)
    {
        return $this->getSession()->getNode($this->getRoutePath($webspaceKey, $languageCode, $segment));
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutePath($webspaceKey, $languageCode, $segment = null)
    {
        $path = sprintf(
            '/%s/%s/%s/%s%s',
            $this->nodeNames['base'],
            $webspaceKey,
            $this->nodeNames['route'],
            $languageCode,
            ($segment !== null ? '/' . $segment : '')
        );

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentNode($webspaceKey)
    {
        return $this->getSession()->getNode($this->getContentPath($webspaceKey));
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getWebspaceNode($webspaceKey)
    {
        return $this->getSession()->getNode($this->getWebspacePath($webspaceKey));
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspacePath($webspaceKey)
    {
        return sprintf(
            '/%s/%s',
            $this->nodeNames['base'],
            $webspaceKey
        );
    }

    /**
     * {@inheritdoc}
     */
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
