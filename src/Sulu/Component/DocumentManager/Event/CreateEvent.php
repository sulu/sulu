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

class CreateEvent extends Event
{
    /**
     * @var object $document
     */
    private $document;

    /**
     * @var string $alias
     */
    private $alias;

    /**
     * @param string $alias
     */
    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function getDocument() 
    {
        if (!$this->document) {
            throw new \RuntimeException(
                'No document has been set, an event listener should have created a document before ' .
                'this method was called.'
            );
        }

        return $this->document;
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getAlias() 
    {
        return $this->alias;
    }
    
}

