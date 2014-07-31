<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

use PHPCR\ItemInterface;
use PHPCR\CredentialsInterface;
use PHPCR\PHPCR\NodeInterface;

/**
 * This trait fulfils the PHPCR\PHPCR\NodeInterface
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
trait SessionTrait
{
    use WrapperAwareTrait;
    use WrappedObjectTrait;

    public function getRepository()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getRepository(), 'PHPCR\RepositoryInterface');
    }

    public function getUserID()
    {
        return $this->getWrappedObject()->getUserID();
    }

    public function getAttributeNames()
    {
        return $this->getWrappedObject()->getAttributeNames();
    }

    public function getAttribute($name)
    {
        return $this->getWrappedObject()->getAttribute($name);
    }

    public function getWorkspace()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getWorkspace(), 'PHPCR\WorkspaceInterface');
    }

    public function getRootNode()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getRootNode(), 'PHPCR\NodeInterface');
    }

    public function impersonate(CredentialsInterface $credentials)
    {
        return $this->getWrappedObject()->impersonate($credentials);
    }

    public function getNodeByIdentifier($id)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getNodeByIdentifier($id), 'PHPCR\NodeInterface');
    }

    public function getNodesByIdentifier($ids)
    {
        return $this->getWrapper()->wrapMany($this->getWrappedObject()->getNodesByIdentifier($ids), 'PHPCR\NodeInterface');
    }

    public function getItem($path)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getItem($path), 'PHPCR\ItemInterface');
    }

    public function getNode($path, $depthHint = -1)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getNode($path), 'PHPCR\NodeInterface');
    }

    public function getNodes($paths)
    {
        return $this->getWrapper()->wrapMany($this->getWrappedObject()->getNodes($paths), 'PHPCR\NodeInterface');
    }

    public function getProperty($path)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getProperty($path), 'PHPCR\PropertyInterface');
    }

    public function getProperties($paths)
    {
        return $this->getWrapper()->wrapMany($this->getWrappedObject()->getProperties($paths), 'PHPCR\PropertyInterface');
    }

    public function itemExists($path)
    {
        return $this->getWrappedObject()->itemExists($path);
    }

    public function nodeExists($path)
    {
        return $this->getWrappedObject()->nodeExists($path);
    }

    public function propertyExists($path)
    {
        return $this->getWrappedObject()->propertyExists($path);
    }

    public function move($srcAbsPath, $destAbsPath)
    {
        return $this->getWrappedObject()->move($srcAbsPath, $destAbsPath);
    }

    public function removeItem($path)
    {
        return $this->getWrappedObject()->removeItem($path);
    }

    public function save()
    {
        return $this->getWrappedObject()->save();
    }

    public function refresh($keepChanges)
    {
        return $this->getWrappedObject()->refresh($keepChanges);
    }

    public function hasPendingChanges()
    {
        return $this->getWrappedObject()->hasPendingChanges();
    }

    public function hasPermission($path, $actions)
    {
        return $this->getWrappedObject()->hasPermission($path, $actions);
    }

    public function checkPermission($path, $actions)
    {
        return $this->getWrappedObject()->checkPermission($path, $actions);
    }

    public function hasCapability($methodName, $target, array $arguments)
    {
        return $this->getWrappedObject()->hasCapability($methodName, $target, $arguments);
    }

    public function importXML($parentAbsPath, $uri, $uuidBehavior)
    {
        return $this->getWrappedObject()->importXML($parentAbsPath, $uri, $uuidBehavior);
    }

    public function exportSystemView($path, $stream, $skipBinary, $noRecurse)
    {
        return $this->getWrappedObject()->exportSystemView($path, $stream, $skipBinary, $noRecurse);
    }

    public function exportDocumentView($path, $stream, $skipBinary, $noRecurse)
    {
        return $this->getWrappedObject()->exportDocumentView($path, $stream, $skipBinary, $noRecurse);
    }

    public function setNamespacePrefix($prefix, $uri)
    {
        return $this->getWrappedObject()->setNamespacePrefix($prefix, $uri);
    }

    public function getNamespacePrefixes()
    {
        return $this->getWrappedObject()->getNamespacePrefixes();
    }

    public function getNamespaceURI($prefix)
    {
        return $this->getWrappedObject()->getNamespaceURI($prefix);
    }

    public function getNamespacePrefix($uri)
    {
        return $this->getWrappedObject()->getNamespacePrefix($uri);
    }

    public function logout()
    {
        return $this->getWrappedObject()->logout();
    }

    public function isLive()
    {
        return $this->getWrappedObject()->isLive();
    }

    public function getAccessControlManager()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getAccessControlManager(), 'PHPCR\Security\AccessControlManagerInterface');
    }

    public function getRetentionManager()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getRetentionManager(), 'PHPCR\Retention\RetentionManagerInterface');
    }

    public function executeQuery($jcrSql2)
    {
        $res = $this->getWorkspace()->getQueryManager()->createQuery($jcrSql2, 'JCR-SQL2')->execute();

        return $this->getWrapper()->wrapMany($res->getNodes(), 'PHPCR\NodeInterface');
    }
}
