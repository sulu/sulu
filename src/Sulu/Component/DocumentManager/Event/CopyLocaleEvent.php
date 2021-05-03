<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Event;

use Sulu\Component\DocumentManager\DocumentHelper;

class CopyLocaleEvent extends AbstractMappingEvent
{
    /**
     * @var string
     */
    private $destLocale;

    /**
     * @var object|null
     */
    private $destDocument = null;

    /**
     * @param object $document
     * @param string $locale
     * @param string $destLocale
     */
    public function __construct($document, $locale, $destLocale)
    {
        $this->document = $document;
        $this->locale = $locale;

        $this->destLocale = $destLocale;
    }

    /**
     * @return string
     */
    public function getDestLocale()
    {
        return $this->destLocale;
    }

    /**
     * @return bool
     */
    public function hasDestDocument()
    {
        return null !== $this->destDocument;
    }

    /**
     * @return object
     */
    public function getDestDocument()
    {
        if (!$this->destDocument) {
            throw new \RuntimeException(\sprintf(
                'Trying to retrieve destination document, but it has not been set. An event ' .
                'listener should have set the destination document when copying locale from document "%s"',
                DocumentHelper::getDebugTitle($this->document)
            ));
        }

        return $this->destDocument;
    }

    /**
     * @param object $destDocument
     *
     * @return void
     */
    public function setDestDocument($destDocument)
    {
        $this->destDocument = $destDocument;
    }
}
