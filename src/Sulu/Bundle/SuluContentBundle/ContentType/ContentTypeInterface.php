<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ContentType;

use PHPCR\NodeInterface;

/**
 * Defines an interface for the content types
 * @package Sulu\Bundle\ContentBundle\ContentType
 */
interface ContentTypeInterface
{
    /**
     * Saves the data of a complex data type
     * @param NodeInterface $node The node containing this complex data type
     * @param $data mixed The data to save for this data type (differs from type to type)
     */
    public function save(NodeInterface $node, $data);
}
