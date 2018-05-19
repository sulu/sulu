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

class PublishEvent extends AbstractMappingEvent
{
    /**
     * @param object $document
     * @param string $locale
     * @param array $options
     */
    public function __construct($document, $locale, array $options = [])
    {
        $this->document = $document;
        $this->locale = $locale;
        $this->options = $options;
    }

    /**
     * Sets the node this event should operate on.
     *
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }
}
