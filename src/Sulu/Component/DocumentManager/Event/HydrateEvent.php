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

class HydrateEvent extends AbstractMappingEvent
{
    /**
     * @param NodeInterface $node
     * @param string $locale
     * @param array $options
     */
    public function __construct(NodeInterface $node, $locale, array $options = [])
    {
        $this->locale = $locale;
        $this->node = $node;
        $this->options = $options;
    }

    /**
     * @return object
     *
     * @throws \RuntimeException
     */
    public function getDocument()
    {
        if (null === $this->document) {
            throw new \RuntimeException(
                'Trying to retrieve document when no document has been set. An event ' .
                'listener should have set the document.'
            );
        }

        return $this->document;
    }

    /**
     * @param object $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
        $this->accessor = null;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
