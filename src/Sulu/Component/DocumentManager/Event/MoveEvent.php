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

class MoveEvent extends AbstractEvent
{
    /**
     * @var object
     */
    private $document;

    /**
     * @var string
     */
    private $destId;

    /**
     * @var string
     */
    private $destName;

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
            'd:%s did:%s, dnam:%s',
            $this->document ? spl_object_hash($this->document) : '<no document>',
            $this->destId ?: '<no dest>',
            $this->destName ?: '<no dest name>'
        );
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
    public function getDestId()
    {
        return $this->destId;
    }

    /**
     * @param string $name
     */
    public function setDestName($name)
    {
        $this->destName = $name;
    }

    /**
     * @return bool
     */
    public function hasDestName()
    {
        return null !== $this->destName;
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getDestName()
    {
        if (!$this->destName) {
            throw new \RuntimeException(sprintf(
                'No destName set in copy/move event when copying/moving document "%s" to "%s". ' .
                'This should have been set by a listener',
                spl_object_hash($this->document),
                $this->destId
            ));
        }

        return $this->destName;
    }
}
