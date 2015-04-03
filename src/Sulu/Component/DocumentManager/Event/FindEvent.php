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
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

class FindEvent extends Event
{
    private $id;
    private $document;
    private $locale;
    private $aliasOrClass;

    public function __construct($id, $locale, $aliasOrClass = null)
    {
        $this->id = $id;
        $this->locale = $locale;
        $this->aliasOrClass = $aliasOrClass;
    }

    public function getId() 
    {
        return $this->id;
    }

    public function getLocale() 
    {
        return $this->locale;
    }

    public function getAliasOrClass() 
    {
        return $this->aliasOrClass;
    }

    public function getDocument() 
    {
        if (!$this->document) {
            throw new DocumentManagerException(sprintf(
                'No document has been set for the findEvent for "%s". An event listener should have done this.',
                $this->id
            ));
        }

        return $this->document;
    }

    public function setDocument($document)
    {
        $this->document = $document;
    }
}

