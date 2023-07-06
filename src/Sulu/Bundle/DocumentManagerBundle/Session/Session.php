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

    public function getRepository()
    {
        return $this->inner->getRepository();
    }

    public function getUserID()
    {
        return $this->inner->getUserID();
    }

    public function getAttributeNames()
    {
        return $this->inner->getAttributeNames();
    }

    public function getAttribute($name)
    {
        return $this->inner->getAttribute($name);
    }

    public function getWorkspace()
    {
        return $this->inner->getWorkspace();
    }

    public function getRootNode()
    {
        return $this->inner->getRootNode();
    }

    public function impersonate(CredentialsInterface $credentials)
    {
        return $this->inner->impersonate($credentials);
    }

    public function getNodeByIdentifier($id)
    {
        return $this->inner->getNodeByIdentifier($id);
    }

    public function getNodesByIdentifier($ids)
    {
        return $this->inner->getNodesByIdentifier($ids);
    }

    public function getItem($absPath)
    {
        return $this->inner->getItem($absPath);
    }

    public function getNode($absPath, $depthHint = -1)
    {
        return $this->inner->getNode($absPath, $depthHint);
    }

    public function getNodes($absPaths)
    {
        return $this->inner->getNodes($absPaths);
    }

    public function getProperty($absPath)
    {
        return $this->inner->getProperty($absPath);
    }

    public function getProperties($absPaths)
    {
        return $this->inner->getProperties($absPaths);
    }

    public function itemExists($absPath)
    {
        return $this->inner->itemExists($absPath);
    }

    public function nodeExists($absPath)
    {
        return $this->inner->nodeExists($absPath);
    }

    public function propertyExists($absPath)
    {
        return $this->inner->propertyExists($absPath);
    }

    public function move($srcAbsPath, $destAbsPath)
    {
        return $this->inner->move($srcAbsPath, $destAbsPath);
    }

    public function removeItem($absPath)
    {
        return $this->inner->removeItem($absPath);
    }

    public function save()
    {
        return $this->inner->save();
    }

    public function refresh($keepChanges)
    {
        return $this->inner->refresh($keepChanges);
    }

    public function hasPendingChanges()
    {
        return $this->inner->hasPendingChanges();
    }

    public function hasPermission($absPath, $actions)
    {
        return $this->inner->hasPermission($absPath, $actions);
    }

    public function checkPermission($absPath, $actions)
    {
        return $this->inner->checkPermission($absPath, $actions);
    }

    public function hasCapability($methodName, $target, array $arguments)
    {
        return $this->inner->hasCapability($methodName, $target, $arguments);
    }

    public function importXML($parentAbsPath, $uri, $uuidBehavior)
    {
        return $this->inner->importXML($parentAbsPath, $uri, $uuidBehavior);
    }

    public function exportSystemView($absPath, $stream, $skipBinary, $noRecurse)
    {
        $memoryStream = \fopen('php://memory', 'w+');
        $this->inner->exportSystemView($absPath, $memoryStream, $skipBinary, $noRecurse);

        \rewind($memoryStream);
        $content = \stream_get_contents($memoryStream);

        $document = new \DOMDocument();
        $document->loadXML($content);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('sv', 'http://www.jcp.org/jcr/sv/1.0');

        foreach ($xpath->query('//sv:property[@sv:name="sulu:versions" or @sv:name="jcr:versionHistory" or @sv:name="jcr:baseVersion" or @sv:name="jcr:predecessors" or @sv:name="jcr:isCheckedOut"]') as $element) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
        }

        \fwrite($stream, $document->saveXML());
    }

    public function exportDocumentView($absPath, $stream, $skipBinary, $noRecurse)
    {
        return $this->inner->exportDocumentView($absPath, $stream, $skipBinary, $noRecurse);
    }

    public function setNamespacePrefix($prefix, $uri)
    {
        return $this->inner->setNamespacePrefix($prefix, $uri);
    }

    public function getNamespacePrefixes()
    {
        return $this->inner->getNamespacePrefixes();
    }

    public function getNamespaceURI($prefix)
    {
        return $this->inner->getNamespaceURI($prefix);
    }

    public function getNamespacePrefix($uri)
    {
        return $this->inner->getNamespacePrefix($uri);
    }

    public function logout()
    {
        return $this->inner->logout();
    }

    public function isLive()
    {
        return $this->inner->isLive();
    }

    public function getAccessControlManager()
    {
        return $this->inner->getAccessControlManager();
    }

    public function getRetentionManager()
    {
        return $this->inner->getRetentionManager();
    }
}
