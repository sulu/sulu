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
     * @var string[]
     */
    private $destLocales;

    /**
     * @param object $document
     * @param string $locale
     * @param string|string[] $destLocales
     */
    public function __construct($document, $locale, $destLocales)
    {
        $this->document = $document;
        $this->locale = $locale;

        if (!\is_array($destLocales)) {
            $destLocales = [$destLocales];
        }

        $this->destLocales = $destLocales;
    }

    /**
     * @return string[]
     */
    public function getDestLocales()
    {
        return $this->destLocales;
    }
}
