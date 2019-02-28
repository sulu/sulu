<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Session;

use PHPCR\CredentialsInterface;
use PHPCR\SessionInterface;

/**
 * Used to wrap the PHPCR session and add some Sulu specific logic on top of it.
 */
class Session implements SessionInterface
{
    /**
     * @var SessionInterface
     */
    private $inner;

    public function __construct(SessionInterface $inner)
    {
        $this->inner = $inner;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->inner->getRepository();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserID()
    {
        return $this->inner->getUserID();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeNames()
    {
        return $this->inner->getAttributeNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name)
    {
        return $this->inner->getAttribute($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorkspace()
    {
        return $this->inner->getWorkspace();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootNode()
    {
        return $this->inner->getRootNode();
    }

    /**
     * {@inheritdoc}
     */
    public function impersonate(CredentialsInterface $credentials)
    {
        return $this->inner->impersonate($credentials);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeByIdentifier($id)
    {
        return $this->inner->getNodeByIdentifier($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesByIdentifier($ids)
    {
        return $this->inner->getNodesByIdentifier($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($absPath)
    {
        return $this->inner->getItem($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getNode($absPath, $depthHint = -1)
    {
        return $this->inner->getNode($absPath, $depthHint);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes($absPaths)
    {
        return $this->inner->getNodes($absPaths);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($absPath)
    {
        return $this->inner->getProperty($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($absPaths)
    {
        return $this->inner->getProperties($absPaths);
    }

    /**
     * {@inheritdoc}
     */
    public function itemExists($absPath)
    {
        return $this->inner->itemExists($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function nodeExists($absPath)
    {
        return $this->inner->nodeExists($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyExists($absPath)
    {
        return $this->inner->propertyExists($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        return $this->inner->move($srcAbsPath, $destAbsPath);
    }

    /**
     * {@inheritdoc}
     */
    public function removeItem($absPath)
    {
        return $this->inner->removeItem($absPath);
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        return $this->inner->save();
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($keepChanges)
    {
        return $this->inner->refresh($keepChanges);
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingChanges()
    {
        return $this->inner->hasPendingChanges();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPermission($absPath, $actions)
    {
        return $this->inner->hasPermission($absPath, $actions);
    }

    /**
     * {@inheritdoc}
     */
    public function checkPermission($absPath, $actions)
    {
        return $this->inner->checkPermission($absPath, $actions);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCapability($methodName, $target, array $arguments)
    {
        return $this->inner->hasCapability($methodName, $target, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function importXML($parentAbsPath, $uri, $uuidBehavior)
    {
        return $this->inner->importXML($parentAbsPath, $uri, $uuidBehavior);
    }

    /**
     * {@inheritdoc}
     */
    public function exportSystemView($absPath, $stream, $skipBinary, $noRecurse)
    {
        $memoryStream = fopen('php://memory', 'w+');
        $this->inner->exportSystemView($absPath, $memoryStream, $skipBinary, $noRecurse);

        rewind($memoryStream);
        $content = stream_get_contents($memoryStream);

        $document = new \DOMDocument();
        $document->loadXML($content);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sv', 'http://www.jcp.org/jcr/sv/1.0');

        foreach ($xpath->query('//sv:property[@sv:name="sulu:versions" or @sv:name="jcr:versionHistory" or @sv:name="jcr:baseVersion" or @sv:name="jcr:predecessors" or @sv:name="jcr:isCheckedOut"]') as $element) {
            $element->parentNode->removeChild($element);
        }

        fwrite($stream, $document->saveXML());
    }

    /**
     * {@inheritdoc}
     */
    public function exportDocumentView($absPath, $stream, $skipBinary, $noRecurse)
    {
        return $this->inner->exportDocumentView($absPath, $stream, $skipBinary, $noRecurse);
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespacePrefix($prefix, $uri)
    {
        return $this->inner->setNamespacePrefix($prefix, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespacePrefixes()
    {
        return $this->inner->getNamespacePrefixes();
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespaceURI($prefix)
    {
        return $this->inner->getNamespaceURI($prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespacePrefix($uri)
    {
        return $this->inner->getNamespacePrefix($uri);
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        return $this->inner->logout();
    }

    /**
     * {@inheritdoc}
     */
    public function isLive()
    {
        return $this->inner->isLive();
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessControlManager()
    {
        return $this->inner->getAccessControlManager();
    }

    /**
     * {@inheritdoc}
     */
    public function getRetentionManager()
    {
        return $this->inner->getRetentionManager();
    }
}
