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

class MoveEvent extends Event
{
    /**
     * @var object $document
     */
    private $document;

    /**
     * @var string $destId
     */
    private $destId;

    /**
     * @param object $document
     */
    public function __construct($document, $destId)
    {
        $this->document = $document;
        $this->destId = $destId;
    }

    public function getDocument() 
    {
        return $this->document;
    }

    public function getDestId() 
    {
        return $this->destId;
    }
    
}

