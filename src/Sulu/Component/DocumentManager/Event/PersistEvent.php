<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use PHPCR\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

class PersistEvent extends Event
{
    /**
     * @var object $document
     */
    private $document;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var string $locale
     */
    private $locale;

    /**
     * @param object $document
     */
    public function __construct($document, $locale)
    {
        $this->document = $document;
        $this->locale = $locale;
    }

    /**
     * @param NodeInterface $node
     */
    public function setNode(NodeInterface $node)
    {
        $this->node = $node;
    }

    /**
     * @return NodeInterface
     * @throws \RuntimeException
     */
    public function getNode()
    {
        if (!$this->node) {
            throw new \RuntimeException(
                'Trying to retrieve node when no node has been set. An event ' .
                'listener should have set the node.'
            );
        }

        return $this->node;
    }

    public function hasNode()
    {
        return null !== $this->node;
    }

    /**
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @return string
     */
    public function getLocale() 
    {
        return $this->locale;
    }
    
}
