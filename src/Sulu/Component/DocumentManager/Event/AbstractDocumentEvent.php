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

abstract class AbstractDocumentEvent extends AbstractEvent
{
    /**
     * @var object
     */
    private $document;

    /**
     * @param object $document
     */
    public function __construct($document)
    {
        $this->document = $document;
    }

    /**
     * @return object
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function getDebugMessage()
    {
        return sprintf(
            'd:%s',
            $this->document ? spl_object_hash($this->document) : '<no document>'
        );
    }
}
