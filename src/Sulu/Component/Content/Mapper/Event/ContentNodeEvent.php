<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Event;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * An instance of this class is thrown along with the sulu.content.node.save event.
 *
 * @deprecated use events of DocumentManager instead
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
