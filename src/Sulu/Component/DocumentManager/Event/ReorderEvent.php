<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use PHPCR\NodeInterface;

class ReorderEvent extends AbstractMappingEvent
{
    /**
     * @var string|null
     */
    private $destId;

    /**
     * Creates a re-ordering event. If the destId is null then the element will be sorted to the end.
     *
     * @param object $document
     * @param string|null $destId
     */
    public function __construct($document, $destId)
    {
        $this->document = $document;
        $this->destId = $destId;
    }

    public function getDebugMessage()
    {
        return \sprintf(
            '%s did:%s',
            parent::getDebugMessage(),
            $this->destId ?: '<no dest>'
        );
    }

    /**
     * @return string|null
     */
    public function getDestId()
    {
        return $this->destId;
    }

    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }
}
