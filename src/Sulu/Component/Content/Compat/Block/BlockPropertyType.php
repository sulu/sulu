<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\PropertyType;

/**
 * representation of a block type node in template xml.
 */
class BlockPropertyType extends PropertyType
{
    /**
     * @var array
     */
    private $settings = [];

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param string $languageCode
     *
     * @return string
     */
    public function getTitle($languageCode)
    {
        return $this->getMetadata()->get('title', $languageCode, \ucfirst($this->getName()));
    }
}
