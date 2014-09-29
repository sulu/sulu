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
 * Event class for the ContentEvents::NODE_ORDER_BEFORE event
 */
class ContentOrderBeforeEvent extends Event
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var NodeInterface
     */
    protected $beforeTargetNode;

    /**
     * @param NodeInterface $node
     * @param StructureInterface $structure
     */
    public function __construct(NodeInterface $node, NodeInterface $beforeTargetNode)
    {
        $this->node = $node;
        $this->beforeTargetNode = $beforeTargetNode;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @return NodeInterface
     */
    public function getBeforeTargetNode()
    {
        return $this->beforeTargetNode;
    }
}
