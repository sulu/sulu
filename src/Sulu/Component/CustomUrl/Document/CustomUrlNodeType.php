<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document;

use PHPCR\NodeType\NodeTypeDefinitionInterface;

/**
 * Node type for custom-url phpcr-nodes.
 */
class CustomUrlNodeType implements NodeTypeDefinitionInterface
{
    public function getName()
    {
        return 'sulu:custom_url';
    }

    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:base',
        ];
    }

    public function isAbstract()
    {
        return false;
    }

    public function isMixin()
    {
        return true;
    }

    public function hasOrderableChildNodes()
    {
        return false;
    }

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
     * @return string|null the name of the primary item
     *
     * @api
     */
    public function getPrimaryItemName()
    {
        return null;
    }

    public function getDeclaredPropertyDefinitions()
    {
        return [];
    }

    public function getDeclaredChildNodeDefinitions()
    {
        return [];
    }
}
