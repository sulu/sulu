<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Exception;

class TranslatedNodeNotFoundException extends \Exception
{
    /**
     * @param string $uuid
     * @param string $languageCode
     */
    public function __construct(private $uuid, private $languageCode)
    {
        parent::__construct(\sprintf('Node "%s" not found in localization "%s"', $uuid, $languageCode));
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
