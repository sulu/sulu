<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber\PHPCR;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

/**
 * Wraps PHPCR-Node and adds some extra logic to detect property type-changes.
 */
class SuluNode implements \IteratorAggregate, NodeInterface
{
    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @param NodeInterface $node
     */
    public function __construct(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return $this->node->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->node->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getAncestor($depth)
    {
        return $this->node->getAncestor($depth);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->node->getParent();
    }

    /**
     * {@inheritdoc}
     */
    public function getDepth()
    {
        return $this->node->getDepth();
    }

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        return $this->node->getSession();
    }

    /**
     * {@inheritdoc}
     */
    public function isNode()
    {
        return $this->node->isNode();
    }

    /**
     * {@inheritdoc}
     */
    public function isNew()
    {
        return $this->node->isNew();
    }

    /**
     * {@inheritdoc}
     */
    public function isModified()
    {
        return $this->node->isModified();
    }

    /**
     * {@inheritdoc}
     */
    public function isSame(ItemInterface $otherItem)
    {
        return $this->node->isSame($otherItem);
    }

    /**
     * {@inheritdoc}
     */
    public function accept(ItemVisitorInterface $visitor)
    {
        return $this->node->accept($visitor);
    }

    /**
     * {@inheritdoc}
     */
    public function revert()
    {
        return $this->node->revert();
    }

    /**
     * {@inheritdoc}
     */
    public function remove()
    {
        return $this->node->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function addNode($relPath, $primaryNodeTypeName = null)
    {
        return $this->node->addNode($relPath, $primaryNodeTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function addNodeAutoNamed($nameHint = null, $primaryNodeTypeName = null)
    {
        return $this->node->addNodeAutoNamed($nameHint, $primaryNodeTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        return $this->node->orderBefore($srcChildRelPath, $destChildRelPath);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($newName)
    {
        return $this->node->rename($newName);
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty($name, $value, $type = PropertyType::UNDEFINED)
    {
        $oldValue = $this->getPropertyValueWithDefault($name, null);
        if ($oldValue !== null && gettype($value) !== gettype($oldValue)) {
            $this->node->getProperty($name)->remove();
        }

        return $this->node->setProperty($name, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getNode($relPath)
    {
        return $this->node->getNode($relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes($nameFilter = null, $typeFilter = null)
    {
        return $this->node->getNodes($nameFilter, $typeFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodeNames($nameFilter = null, $typeFilter = null)
    {
        return $this->node->getNodeNames($nameFilter, $typeFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($relPath)
    {
        return $this->node->getProperty($relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue($name, $type = PropertyType::UNDEFINED)
    {
        return $this->node->getPropertyValue($name, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValueWithDefault($relPath, $defaultValue)
    {
        return $this->node->getPropertyValueWithDefault($relPath, $defaultValue);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($nameFilter = null)
    {
        return $this->node->getProperties($nameFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertiesValues($nameFilter = null, $dereference = true)
    {
        return $this->node->getPropertiesValues($nameFilter, $dereference);
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryItem()
    {
        return $this->node->getPrimaryItem();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->node->getIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function getIndex()
    {
        return $this->node->getIndex();
    }

    /**
     * {@inheritdoc}
     */
    public function getReferences($name = null)
    {
        return $this->node->getReferences($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getWeakReferences($name = null)
    {
        return $this->node->getWeakReferences($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasNode($relPath)
    {
        return $this->node->hasNode($relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperty($relPath)
    {
        return $this->node->hasProperty($relPath);
    }

    /**
     * {@inheritdoc}
     */
    public function hasNodes()
    {
        return $this->node->hasNodes();
    }

    /**
     * {@inheritdoc}
     */
    public function hasProperties()
    {
        return $this->node->hasProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryNodeType()
    {
        return $this->node->getPrimaryNodeType();
    }

    /**
     * {@inheritdoc}
     */
    public function getMixinNodeTypes()
    {
        return $this->node->getMixinNodeTypes();
    }

    /**
     * {@inheritdoc}
     */
    public function isNodeType($nodeTypeName)
    {
        return $this->node->isNodeType($nodeTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function setPrimaryType($nodeTypeName)
    {
        return $this->node->setPrimaryType($nodeTypeName);
    }

    /**
     * {@inheritdoc}
     */
    public function addMixin($mixinName)
    {
        return $this->node->addMixin($mixinName);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMixin($mixinName)
    {
        return $this->node->removeMixin($mixinName);
    }

    /**
     * {@inheritdoc}
     */
    public function setMixins(array $mixinNames)
    {
        return $this->node->setMixins($mixinNames);
    }

    /**
     * {@inheritdoc}
     */
    public function canAddMixin($mixinName)
    {
        return $this->node->canAddMixin($mixinName);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return $this->node->getDefinition();
    }

    /**
     * {@inheritdoc}
     */
    public function update($srcWorkspace)
    {
        return $this->node->update($srcWorkspace);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrespondingNodePath($workspaceName)
    {
        return $this->node->getCorrespondingNodePath($workspaceName);
    }

    /**
     * {@inheritdoc}
     */
    public function getSharedSet()
    {
        return $this->node->getSharedSet();
    }

    /**
     * {@inheritdoc}
     */
    public function removeSharedSet()
    {
        return $this->node->removeSharedSet();
    }

    /**
     * {@inheritdoc}
     */
    public function removeShare()
    {
        return $this->node->removeShare();
    }

    /**
     * {@inheritdoc}
     */
    public function isCheckedOut()
    {
        return $this->node->isCheckedOut();
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        return $this->node->isLocked();
    }

    /**
     * {@inheritdoc}
     */
    public function followLifecycleTransition($transition)
    {
        return $this->node->followLifecycleTransition($transition);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedLifecycleTransitions()
    {
        return $this->node->getAllowedLifecycleTransitions();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (!$this->node instanceof \IteratorAggregate) {
            return;
        }

        return $this->node->getIterator();
    }
}
