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
use Symfony\Component\EventDispatcher\Event;

/**
 * Event class for the ContentEvents::NODE_ORDER event.
 *
 * @deprecated use events of DocumentManager instead
 */
class ContentNodeOrderEvent extends Event
{
    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @param NodeInterface $node
     */
    public function __construct(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
    }
}
