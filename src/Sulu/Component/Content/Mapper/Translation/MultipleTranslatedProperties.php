<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper\Translation;


use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;

/**
 * enables to translate multiple properties
 * @package Sulu\Component\Content\Mapper\Translation
 */
class MultipleTranslatedProperties
{
    /**
     * @var PropertyInterface[]
     */
    private $properties = array();

    /**
     * @var PropertyInterface[]
     */
    private $translatedProperties;

    /**
     * @var string
     */
    private $languageNamespace;

    function __construct(
        $names,
        $languageNamespace,
        $namespace = 'sulu'
    )
    {
        $this->languageNamespace = $languageNamespace;
        $this->properties = array();
        foreach ($names as $name) {
            $this->properties[$name] = new Property($namespace . '-' . $name, '', 'none', false, true);
        }
    }

    public function setLanguage($languageKey)
    {
        $this->translatedProperties = array();
        foreach ($this->properties as $key => $property) {
            $this->translatedProperties[$key] = new TranslatedProperty(
                $property,
                $languageKey,
                $this->languageNamespace
            );
        }
    }

    public function getName($key)
    {
        if (isset($this->translatedProperties[$key])) {
            return $this->translatedProperties[$key]->getName();
        } else {
            return false;
        }
    }
}
