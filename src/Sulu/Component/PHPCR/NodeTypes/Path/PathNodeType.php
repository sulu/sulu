<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\NodeTypes\Path;

use PHPCR\NodeType\NodeDefinitionInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\NodeType\PropertyDefinitionInterface;

class PathNodeType implements NodeTypeDefinitionInterface
{
    /**
     * Returns the name of the node type.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return string The name of the node type
     *
     * @api
     */
    public function getName()
    {
        return 'sulu:path';
    }

    /**
     * Returns the names of the supertypes actually declared in this node type.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return an array containing a
     * single string indicating the node type nt:base.
     *
     * @return array the names of the declared supertypes
     *
     * @api
     */
    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:base',
        ];
    }

    /**
     * Reports if this is an node type.
     *
     * Returns true if this is an node type; returns false otherwise.
     * An node type is one that cannot be assigned as the primary or
     * mixin type of a node but can be used in the definitions of other node
     * types as a superclass.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return false.
     *
     * @return bool True, if the current type is abstract, else false
     *
     * @api
     */
    public function isAbstract()
    {
        return false;
    }

    /**
     * Reports if this is a mixin node type.
     *
     * Returns true if this is a mixin type; returns false if it is primary.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return false.
     *
     * @return bool True if this is a mixin type, else false;
     *
     * @api
     */
    public function isMixin()
    {
        return true;
    }

    /**
     * Determines if nodes of this type must support orderable child nodes.
     *
     * Returns true if nodes of this type must support orderable child nodes;
     * returns false otherwise. If a node type returns true on a call to this
     * method, then all nodes of that node type must support the method
     * NodeInterface::orderBefore(). If a node type returns false on a call to
     * this method, then nodes of that node type may support
     * NodeInterface::orderBefore(). Only the primary node type of a node
     * controls that node's status in this regard. This setting on a mixin node
     * type will not have any effect on the node.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinitionInterface object is actually a newly-created empty
     * NodeTypeTemplateInterface, then this method will return false.
     *
     * @return bool True, if nodes of this type must support orderable child
     *              nodes, else false
     *
     * @api
     */
    public function hasOrderableChildNodes()
    {
        return false;
    }

    /**
     * Determines if the node type is queryable.
     *
     * Returns true if the node type is queryable, meaning that the
     * available-query-operators, full-text-searchable and query-orderable
     * attributes of its property definitions take effect.
     *
     * If a node type is declared non-queryable then these attributes of its
     * property definitions have no effect.
     *
     * @return bool True, if the node type is queryable, else false
     *
     * @see PropertyDefinition::getAvailableQueryOperators()
     * @see PropertyDefinition::isFullTextSearchable()
     * @see PropertyDefinition::isQueryOrderable()
     *
     * @api
     */
    public function isQueryable()
    {
        return false;
    }

    /**
     * Returns the name of the primary item (one of the child items of the nodes
     * of this node type).
     *
     * If this node has no primary item, then this method returns null. This
     * indicator is used by the method NodeInterface::getPrimaryItem().
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinitionInterface object is actually a newly-created empty
     * NodeTypeTemplateInterface, then this method will return null.
     *
     * @return string The name of the primary item
     *
     * @api
     */
    public function getPrimaryItemName()
    {
        return;
    }

    /**
     * Returns an array containing the property definitions actually declared
     * in this node type.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return PropertyDefinitionInterface[] An array of PropertyDefinitions
     *
     * @api
     */
    public function getDeclaredPropertyDefinitions()
    {
        return [
            new ContentPropertyDefinition(),
            new HistoryPropertyDefinition(),
        ];
    }

    /**
     * Returns an array containing the child node definitions actually
     * declared in this node type.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return NodeDefinitionInterface[] An array of NodeDefinitions
     *
     * @api
     */
    public function getDeclaredChildNodeDefinitions()
    {
        return [];
    }
}
