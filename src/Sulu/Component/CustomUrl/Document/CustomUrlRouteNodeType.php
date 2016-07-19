<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document;

use PHPCR\NodeType\NodeTypeDefinitionInterface;

/**
 * Node type for custom-url-route phpcr-nodes.
 */
class CustomUrlRouteNodeType implements NodeTypeDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu:custom_url_route';
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaredSupertypeNames()
    {
        return [
            'sulu:base',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isAbstract()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isMixin()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOrderableChildNodes()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isQueryable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryItemName()
    {
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaredPropertyDefinitions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaredChildNodeDefinitions()
    {
        return [];
    }
}
