<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal;

/**
 * Represents a localization of a workspace definition
 * @package Sulu\Component\Portal
 */
class Localization
{
    /**
     * The language of the localization
     * @var string
     */
    private $language;

    /**
     * The country of the localizatio
     * @var string
     */
    private $country;

    /**
     * Defines how the generation of shadow pages should be handled
     * @var string
     */
    private $shadow;

    /**
     * Defines if this localization is the default one
     * @var boolean
     */
    private $default;

    /**
     * Sets the country of this localization
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country of this localization
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets if this localization is the default one
     * @param boolean $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Returns if this localization is the default one
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * Sets the language of this localization
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns the language of this localization
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets how to handle shadow pages for this localization
     * @param string $shadow
     */
    public function setShadow($shadow)
    {
        $this->shadow = $shadow;
    }

    /**
     * Returns how to handle shadow pages for this localization
     * @return string
     */
    public function getShadow()
    {
        return $this->shadow;
    }
}
