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

class UnpublishEvent extends AbstractMappingEvent
{
    /**
     * @param object $document
     * @param string $locale
     */
    public function __construct($document, $locale)
    {
        $this->document = $document;
        $this->locale = $locale;
    }

    /**
     * Sets the node this event should operate on.
     *
     * TODO Check if should be move to DocumentManager itself
     *
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }
}
