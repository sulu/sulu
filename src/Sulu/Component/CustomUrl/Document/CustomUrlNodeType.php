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

    public function getPrimaryItemName()
    {
        return;
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
