<?php

namespace Sulu\Component\PHPCR\Wrapper\Traits;

use PHPCR\ItemInterface;

/**
 * This trait fulfils the PHPCR\NodeInterface
 *
 * @author Daniel Leech <daniel@dantleech.com>
 */
trait NodeTrait
{
    use ItemTrait;

    /**
     * @see PHPCR\NodeInterface#addNode
     */
    public function addNode($relPath, $primaryNodeTypeName = null)
    {
        return $this->getWrapper()->wrap($this->call('addNode', func_get_args()), 'PHPCR\NodeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#addNodeAutoNamed
     */
    public function addNodeAutoNamed($nameHint = null, $primaryNodeTypeName = null)
    {
        return $this->getWrapper()->wrap($this->call('addNodeAutoNamed', func_get_args()), 'PHPCR\NodeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#orderBefore
     */
    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        return $this->getWrappedObject()->orderBefore($srcChildRelPath, $destChildRelPath);
    }

    /**
     * @see PHPCR\NodeInterface#rename
     */
    public function rename($newName)
    {
        return $this->getWrappedObject()->rename($newName);
    }

    /**
     * @see PHPCR\NodeInterface#setProperty
     */
    public function setProperty($name, $value, $type = null)
    {
        return $this->call('setProperty', func_get_args());
    }

    /**
     * @see PHPCR\NodeInterface#getNode
     */
    public function getNode($relPath)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getNode($relPath), 'PHPCR\NodeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getNodes
     */
    public function getNodes($nameFilter = null, $typeFilter = null)
    {
        return $this->getWrapper()->wrapMany($this->call('getNodes', func_get_args()), 'PHPCR\NodeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getNodeNames
     */
    public function getNodeNames($nameFilter = null, $typeFilter = null)
    {
        return $this->call('getNodeNames', func_get_args());
    }

    /**
     * @see PHPCR\NodeInterface#getProperty
     */
    public function getProperty($relPath)
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getProperty($relPath), 'PHPCR\PropertyInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getPropertyValue
     */
    public function getPropertyValue($name, $type=null)
    {
        return $this->call('getPropertyValue', func_get_args());
    }

    /**
     * @see PHPCR\NodeInterface#getPropertyValueWithDefault
     */
    public function getPropertyValueWithDefault($relPath, $defaultValue)
    {
        return $this->getWrappedObject()->getPropertyValueWithDefault($relPath, $defaultValue);
    }

    /**
     * @see PHPCR\NodeInterface#getProperties
     */
    public function getProperties($nameFilter = null)
    {
        return $this->getWrapper()->wrapMany($this->call('getProperties', func_get_args()), 'PHPCR\PropertyInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getPropertiesValues
     */
    public function getPropertiesValues($nameFilter=null, $dereference=true)
    {
        return $this->call('getPropertiesValues', func_get_args());
    }

    /**
     * @see PHPCR\NodeInterface#getPrimaryItem
     */
    public function getPrimaryItem()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getPrimaryItem(), 'PHPCR\ItemInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getIdentifier
     */
    public function getIdentifier()
    {
        return $this->getWrappedObject()->getIdentifier();
    }

    /**
     * @see PHPCR\NodeInterface#getIndex
     */
    public function getIndex()
    {
        return $this->getWrappedObject()->getIndex();
    }

    /**
     * @see PHPCR\NodeInterface#getReferences
     */
    public function getReferences($name = null)
    {
        return $this->getWrapper()->wrapMany($this->call('getReferences', func_get_args()), 'PHPCR\PropertyInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getWeakReferences
     */
    public function getWeakReferences($name = null)
    {
        return $this->getWrapper()->wrapMany($this->call('getWeakReferences', func_get_args()), 'PHPCR\PropertyInterface');
    }

    /**
     * @see PHPCR\NodeInterface#hasNode
     */
    public function hasNode($relPath)
    {
        return $this->getWrappedObject()->hasNode($relPath);
    }

    /**
     * @see PHPCR\NodeInterface#hasProperty
     */
    public function hasProperty($relPath)
    {
        return $this->getWrappedObject()->hasProperty($relPath);
    }

    /**
     * @see PHPCR\NodeInterface#hasNodes
     */
    public function hasNodes()
    {
        return $this->getWrappedObject()->hasNodes();
    }

    /**
     * @see PHPCR\NodeInterface#hasProperties
     */
    public function hasProperties()
    {
        return $this->getWrappedObject()->hasProperties();
    }

    /**
     * @see PHPCR\NodeInterface#getPrimaryNodeType
     */
    public function getPrimaryNodeType()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getPrimaryNodeType(), 'PHPCR\NodeType\NodeTypeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#getMixinNodeTypes
     */
    public function getMixinNodeTypes()
    {
        return $this->getWrapper()->wrapMany($this->getWrappedObject()->getMixinNodeTypes(), 'PHPCR\NodeType\NodeTypeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#isNodeType
     */
    public function isNodeType($nodeTypeName)
    {
        return $this->getWrappedObject()->isNodeType($nodeTypeName);
    }

    /**
     * @see PHPCR\NodeInterface#setPrimaryType
     */
    public function setPrimaryType($nodeTypeName)
    {
        return $this->getWrappedObject()->setPrimaryType($nodeTypeName);
    }

    /**
     * @see PHPCR\NodeInterface#addMixin
     */
    public function addMixin($mixinName)
    {
        return $this->getWrappedObject()->addMixin($mixinName);
    }

    /**
     * @see PHPCR\NodeInterface#removeMixin
     */
    public function removeMixin($mixinName)
    {
        return $this->getWrappedObject()->removeMixin($mixinName);
    }

    /**
     * @see PHPCR\NodeInterface#setMixins
     */
    public function setMixins(array $mixinNames)
    {
        return $this->getWrappedObject()->setMixins($mixinNames);
    }

    /**
     * @see PHPCR\NodeInterface#canAddMixin
     */
    public function canAddMixin($mixinName)
    {
        return $this->getWrappedObject()->canAddMixin($mixinName);
    }

    /**
     * @see PHPCR\NodeInterface#getDefinition
     */
    public function getDefinition()
    {
        return $this->getWrapper()->wrap($this->getWrappedObject()->getDefinition(), 'PHPCR\NodeType\NodeDefinitionInterface');
    }

    /**
     * @see PHPCR\NodeInterface#update
     */
    public function update($srcWorkspace)
    {
        return $this->getWrappedObject()->update($srcWorkspace);
    }

    /**
     * @see PHPCR\NodeInterface#getCorrespondingNodePath
     */
    public function getCorrespondingNodePath($workspaceName)
    {
        return $this->getWrappedObject()->getCorrespondingNodePath($workspaceName);
    }

    /**
     * @see PHPCR\NodeInterface#getSharedSet
     */
    public function getSharedSet()
    {
        return $this->getWrapper()->wrapMany($this->getWrappedObject()->getSharedSet(), 'PHPCR\NodeInterface');
    }

    /**
     * @see PHPCR\NodeInterface#removeSharedSet
     */
    public function removeSharedSet()
    {
        return $this->getWrappedObject()->removeSharedSet();
    }

    /**
     * @see PHPCR\NodeInterface#removeShare
     */
    public function removeShare()
    {
        return $this->getWrappedObject()->removeShare();
    }

    /**
     * @see PHPCR\NodeInterface#isCheckedOut
     */
    public function isCheckedOut()
    {
        return $this->getWrappedObject()->isCheckedOut();
    }

    /**
     * @see PHPCR\NodeInterface#isLocked
     */
    public function isLocked()
    {
        return $this->getWrappedObject()->isLocked();
    }

    /**
     * @see PHPCR\NodeInterface#followLifecycleTransition
     */
    public function followLifecycleTransition($transition)
    {
        return $this->getWrappedObject()->followLifecycleTransition($transition);
    }

    /**
     * @see PHPCR\NodeInterface#getAllowedLifecycleTransitions
     */
    public function getAllowedLifecycleTransitions()
    {
        return $this->getWrappedObject()->getAllowedLifecycleTransitions();
    }

    /**
     * {@inheritDoc}
     */
    public function getPath()
    {
        return $this->getWrappedObject()->getPath();
    }
}
