<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * Represents a localization of a webspace definition
 * @package Sulu\Component\Portal
 */
class Localization implements \JsonSerializable
{
    /**
     * The language of the localization
     * @var string
     */
    private $language;

    /**
     * The country of the localization
     * @var string
     */
    private $country;

    /**
     * Defines how the generation of shadow pages should be handled
     * @var string
     */
    private $shadow;

    /**
     * The sub localizations of this one
     * @var Localization[]
     */
    private $children;

    /**
     * The parent localization
     * @var Localization
     */
    private $parent;

    /**
     * Defines whether this localization is the default one or not
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

    /**
     * Adds a new child localization
     * @param Localization $child
     */
    public function addChild(Localization $child)
    {
        $this->children[] = $child;
    }

    /**
     * Sets the children of the localization
     * @param \Sulu\Component\Webspace\Localization[] $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Returns the children of the localization
     * @return \Sulu\Component\Webspace\Localization[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns the localization code, which is a combination of the language and the country
     * @param string $delimiter between language and country
     * @return string
     */
    public function getLocalization($delimiter = '_')
    {
        $localization = $this->getLanguage();
        if ($this->getCountry() != null) {
            $localization .= $delimiter . $this->getCountry();
        }

        return $localization;
    }

    /**
     * Sets the parent of this localization
     * @param \Sulu\Component\Webspace\Localization $parent
     */
    public function setParent(Localization $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent of this localization
     * @return \Sulu\Component\Webspace\Localization
     */
    public function getParent()
    {
        return $this->parent;
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
     * @return boolean True if this is the default localization, otherwise false
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @param string $localization
     * @internal param \Sulu\Component\Webspace\Localization $this
     * @return Localization|null
     */
    public function findLocalization($localization)
    {
        if ($this->getLocalization() == $localization) {
            return $this;
        }

        $children = $this->getChildren();
        if (!empty($children)) {
            foreach ($children as $childLocalization) {
                $result = $childLocalization->findLocalization($localization);
                if ($result) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Returns a list of all localizations and sublocalizations
     * @return \Sulu\Component\Webspace\Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = array();
        if ($this->getChildren() !== null && sizeof($this->getChildren()) > 0) {
            foreach ($this->getChildren() as $child) {
                $localizations[] = $child;
                $localizations = array_merge($localizations, $child->getAllLocalizations());
            }
        }

        return $localizations;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getLocalization();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return array(
            'localization' => $this->getLocalization(),
            'name' => $this->getLocalization()
        );
    }
}
