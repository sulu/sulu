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

class CopyLocaleEvent extends AbstractMappingEvent
{
    /**
     * @var string
     */
    private $destLocale;

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
}
