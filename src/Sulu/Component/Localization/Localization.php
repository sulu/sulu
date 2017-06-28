<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization;

use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Component\Util\ArrayableInterface;

/**
 * Represents a localization of a webspace definition.
 */
class Localization implements \JsonSerializable, ArrayableInterface
{
    const UNDERSCORE = 'de_at';
    const DASH = 'de-at';
    const ISO6391 = 'de-AT';
    const LCID = 'de_AT';

    /**
     * Create an instance of localization for given locale.
     *
     * @param string $locale
     * @param string $format
     *
     * @return Localization
     */
    public static function createFromString($locale, $format = self::UNDERSCORE)
    {
        $delimiter = '-';
        if (in_array($format, [self::UNDERSCORE, self::LCID])) {
            $delimiter = '_';
        }

        $parts = explode($delimiter, $locale);

        $localization = new self();
        $localization->setLanguage(strtolower($parts[0]));
        if (count($parts) > 1) {
            $localization->setCountry(strtolower($parts[1]));
        }

        return $localization;
    }

    /**
     * The language of the localization.
     *
     * @var string
     */
    private $language;

    /**
     * The country of the localization.
     *
     * @var string
     */
    private $country;

    /**
     * Defines how the generation of shadow pages should be handled.
     *
     * @var string
     */
    private $shadow;

    /**
     * The sub localizations of this one.
     *
     * @var Localization[]
     */
    private $children;

    /**
     * The parent localization.
     *
     * @var Localization
     */
    private $parent;

    /**
     * Defines whether this localization is the default one or not.
     *
     * @var bool
     */
    private $default;

    /**
     * Defines whether this localization is the x-default one or not.
     * This will be used to determine the default hreflang tag.
     *
     * @var
     */
    private $xDefault;

    public function __construct($language = null, $country = null)
    {
        $this->language = $language;
        $this->country = $country;
    }

    /**
     * Sets the country of this localization.
     *
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Returns the country of this localization.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Sets the language of this localization.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Returns the language of this localization.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets how to handle shadow pages for this localization.
     *
     * @param string $shadow
     */
    public function setShadow($shadow)
    {
        $this->shadow = $shadow;
    }

    /**
     * Returns how to handle shadow pages for this localization.
     *
     * @return string
     */
    public function getShadow()
    {
        return $this->shadow;
    }

    /**
     * Adds a new child localization.
     *
     * @param Localization $child
     */
    public function addChild(Localization $child)
    {
        $this->children[] = $child;
    }

    /**
     * Sets the children of the localization.
     *
     * @param Localization[] $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Returns the children of the localization.
     *
     * @return Localization[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns the localization code, which is a combination of the language and the country.
     *
     * @param string $delimiter between language and country
     *
     * @return string
     * @VirtualProperty
     *
     * @deprecated use getLocale instead
     */
    public function getLocalization($delimiter = '_')
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.2 and will be removed in 2.0. Use getLocale() instead.', E_USER_DEPRECATED);

        $localization = $this->getLanguage();
        if ($this->getCountry() != null) {
            $localization .= $delimiter . $this->getCountry();
        }

        return $localization;
    }

    /**
     * Returns the localization code, which is a combination of the language and the country in a specific format.
     *
     * @param string $format requested localization format
     *
     * @return string
     * @VirtualProperty
     */
    public function getLocale($format = self::UNDERSCORE)
    {
        $localization = strtolower($this->getLanguage());

        if (null != $this->getCountry()) {
            $country = strtolower($this->getCountry());
            $delimiter = '-';

            switch ($format) {
                case self::UNDERSCORE:
                    $delimiter = '_';
                    break;
                case self::ISO6391:
                    $country = strtoupper($country);
                    break;
                case self::LCID:
                    $delimiter = '_';
                    $country = strtoupper($country);
                    break;
            }

            $localization .= $delimiter . $country;
        }

        return $localization;
    }

    /**
     * Sets the parent of this localization.
     *
     * @param Localization $parent
     */
    public function setParent(Localization $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent of this localization.
     *
     * @return Localization
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets if this localization is the default one.
     *
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * Sets if this localization is the x-default one.
     *
     * @param bool $xDefault
     */
    public function setXDefault($xDefault)
    {
        $this->xDefault = $xDefault;
    }

    /**
     * Returns if this localization is the default one.
     *
     * @return bool True if this is the default localization, otherwise false
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * Returns if this localization is the x-default one.
     *
     * @return bool True if this is the x-default localization, otherwise false
     */
    public function isXDefault()
    {
        return $this->xDefault;
    }

    /**
     * @param string $localization
     *
     * @return Localization|null
     */
    public function findLocalization($localization)
    {
        if ($this->getLocale() == $localization) {
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

        return;
    }

    /**
     * Returns a list of all localizations and sublocalizations.
     *
     * @return Localization[]
     */
    public function getAllLocalizations()
    {
        $localizations = [];
        if ($this->getChildren() !== null && count($this->getChildren()) > 0) {
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
        return $this->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'localization' => $this->getLocale(),
            'name' => $this->getLocale(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($depth = null)
    {
        $res = [];
        $res['country'] = $this->getCountry();
        $res['language'] = $this->getLanguage();
        $res['localization'] = $this->getLocale();
        $res['default'] = $this->isDefault();
        $res['xDefault'] = $this->isXDefault();
        $res['children'] = [];

        $children = $this->getChildren();
        if ($children) {
            foreach ($this->getChildren() as $childLocalization) {
                $res['children'][] = $childLocalization->toArray(null);
            }
        }

        $res['shadow'] = $this->getShadow();

        return $res;
    }
}
