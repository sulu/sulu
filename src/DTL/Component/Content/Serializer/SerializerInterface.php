<?php
/**
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Serializer;

use PHPCR\NodeInterface;

/**
 * Serialize / deserialize content from a PHPCR node
 */
interface SerializerInterface
{
    /**
     * Normalize content data to a flattened list
     *
     * @param NodeInterface $node
     * @param array $data
     *
     * @return NodeInterface
     */
    public function serialize($data, NodeInterface $node);

    /**
     * Extract the content data from the given node
     *
     * @param NodeInterface $node
     *
     * @return array
     */
    public function deserialize(NodeInterface $node);
}

