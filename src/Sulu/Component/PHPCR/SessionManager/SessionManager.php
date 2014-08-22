<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\SessionManager;

use PHPCR\CredentialsInterface;
use PHPCR\NodeInterface;
use PHPCR\RepositoryFactoryInterface;
use PHPCR\RepositoryInterface;
use PHPCR\SessionInterface;
use PHPCR\SimpleCredentials;

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

    function __construct(SessionInterface $session, $nodeNames)
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
        $path = sprintf(
            '%s/%s/%s/%s%s',
            $this->nodeNames['base'],
            $webspaceKey,
            $this->nodeNames['route'],
            $languageCode,
            ($segment !== null ? '/' . $segment : '')
        );
        $root = $this->getSession()->getRootNode();

        return $root->getNode($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentNode($webspaceKey)
    {
        $path = $this->nodeNames['base'] . '/' . $webspaceKey . '/' . $this->nodeNames['content'];
        $root = $this->getSession()->getRootNode();

        return $root->getNode($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTempNode($webspaceKey, $alias)
    {
        $tempPath = $this->nodeNames['base'] . '/' . $webspaceKey . '/' . $this->nodeNames['temp'] . '';
        $tempNode = $this->getSession()->getNode($tempPath, 2);

        // create the node on the fly
        if (!$tempNode->hasNode($alias)) {
            $tempNode->addNode($alias);
        }

        return $tempNode->getNode($alias);
    }
}
