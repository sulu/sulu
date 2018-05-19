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
use Sulu\Component\DocumentManager\DocumentAccessor;

abstract class AbstractMappingEvent extends AbstractEvent
{
    use EventOptionsTrait;

    /**
     * @var object
     */
    protected $document;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var NodeInterface
     */
    protected $node;

    /**
     * @var DocumentAccessor
     */
    protected $accessor;

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            'n:%s d:%s l:%s',
            $this->node ? $this->node->getPath() : '<no node>',
            $this->document ? spl_object_hash($this->document) : '<no document>',
            $this->locale ?: '<no locale>'
        );
    }

    /**
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->node;
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

    /**
     * TODO: Refactor this away.
     *
     * @return DocumentAccessor
     */
    public function getAccessor()
    {
        if ($this->accessor) {
            return $this->accessor;
        }

        $this->accessor = new DocumentAccessor($this->getDocument());

        return $this->accessor;
    }

    /**
     * Return true if the document has been set.
     */
    public function hasDocument()
    {
        return null !== $this->document;
    }

    /**
     * @return bool
     */
    public function hasNode()
    {
        return null !== $this->node;
    }
}
