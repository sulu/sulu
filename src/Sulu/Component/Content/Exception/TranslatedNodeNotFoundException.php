<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class TranslatedNodeNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $languageCode;

    public function __construct($uuid, $languageCode)
    {
        parent::__construct(sprintf('Node "%s" not found in localization "%s"', $uuid, $languageCode));
        $this->uuid = $uuid;
        $this->languageCode = $languageCode;
    }

    /**
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}
