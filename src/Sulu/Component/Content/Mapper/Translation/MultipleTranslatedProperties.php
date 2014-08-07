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
use Sulu\Component\Content\Exception\NoSuchPropertyException;
use PHPCR\NodeInterface;

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
        $namespace = ''
    )
    {
        $this->languageNamespace = $languageNamespace;
        $this->properties = array();
        foreach ($names as $name) {
            $propertyName = (!empty($namespace) ? $namespace . '-' : '') . $name;
            $this->properties[$name] = new Property($propertyName, array(), 'none', false, true);
        }
    }

    /**
     * set language of translated property names
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
     * returns translated property name
     * @param string $key
     * @throws \Sulu\Component\Content\Exception\NoSuchPropertyException
     * @return string
     */
    public function getName($key)
    {
        if (isset($this->translatedProperties[$key])) {
            return $this->translatedProperties[$key]->getName();
        } else {
            throw new NoSuchPropertyException($key);
        }
    }

    public function getLanguagesForNode(NodeInterface $node)
    {
        $languages = array();
        foreach ($node->getProperties() as $property) {
            preg_match('/^' . $this->languageNamespace . ':(.*?)-/', $property->getName(), $matches);

            if ($matches) {
                $languages[$matches[1]] = $matches[1];
            }
        }

        return array_values($languages);
    }
}
