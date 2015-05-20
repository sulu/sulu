<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Event;

use PHPCR\NodeInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An instance of this class is thrown along with the sulu.content.node.save event.
 */
class ContentNodeEvent extends Event
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var StructureInterface
     */
    protected $structure;

    /**
     * @param NodeInterface $node
     * @param StructureInterface $structure
     */
    public function __construct(NodeInterface $node, StructureInterface $structure)
    {
        $this->node = $node;
        $this->structure = $structure;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return StructureInterface
     */
    public function getStructure()
    {
        return $this->structure;
    }
}
