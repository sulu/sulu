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

use Sulu\Component\Content\Exception\NoSuchPropertyException;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\Structure;

/**
 * enables to translate multiple properties.
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

    /**
     * @var string
     */
    private $structureType = Structure::TYPE_PAGE;

    public function __construct(
        $names,
        $languageNamespace,
        $namespace = ''
    ) {
        $this->languageNamespace = $languageNamespace;
        $this->properties = array();
        foreach ($names as $name) {
            $propertyName = (!empty($namespace) ? $namespace . '-' : '') . $name;
            $this->properties[$name] = new Property($propertyName, array(), 'none', false, true);
        }
    }

    /**
     * set language of translated property names.
     *
     * @param string $languageKey
     */
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

    /**
     * returns translated property name.
     *
     * @param string $key
     *
     * @throws \Sulu\Component\Content\Exception\NoSuchPropertyException
     *
     * @return string
     */
    public function getName($key)
    {
        // templates do not translate the template key
        if ($this->structureType === Structure::TYPE_SNIPPET) {
            if ($key === 'template') {
                return $key;
            }
        }

        if (isset($this->translatedProperties[$key])) {
            return $this->translatedProperties[$key]->getName();
        } else {
            throw new NoSuchPropertyException($key);
        }
    }

    /**
     * Set the structure type.
     *
     * @param string $structureType
     */
    public function setStructureType($structureType)
    {
        $this->structureType = $structureType;
    }
}
