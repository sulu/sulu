<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use PHPCR\NodeInterface;

class ReorderEvent extends AbstractMappingEvent
{
    /**
     * @var string
     */
    private $destId;

    /**
     * @param object $document
     * @param string $destId
     */
    public function __construct($document, $destId)
    {
        $this->document = $document;
        $this->destId = $destId;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            '%s did:%s',
            parent::getDebugMessage(),
            $this->destId ?: '<no dest>'
        );
    }

    /**
     * @return string
     */
    public function getDestId()
    {
        return $this->destId;
    }

    /**
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }
}
