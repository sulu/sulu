<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\StructureExtension;

use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

/**
 * basic implementation of content mapper extension
 * @package Sulu\Component\Content\Mapper
 */
abstract class StructureExtension implements StructureExtensionInterface
{

    /**
     * @var string[]
     */
    protected $properties;

    /**
     * additional prefix for properties
     * @var string
     */
    protected $additionalPrefix;

    /**
     * data of extension
     * @var mixed
     */
    protected $data;

    /**
     * name of extension
     * @var string
     */
    protected $name;

    /**
     * @var MultipleTranslatedProperties
     */
    private $translatedProperties;

    /**
     * returns translated property name
     * @param $propertyName
     * @return string
     */
    protected function getPropertyName($propertyName)
    {
        return $this->translatedProperties->getName($propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function setLanguageCode($languageCode, $languageNamespace, $namespace)
    {
        // build namespace
        $namespaces = array();
        if (!empty($namespace)) {
            $namespaces[] = $namespace;
        }
        if (!empty($this->additionalPrefix)) {
            $namespaces[] = $this->additionalPrefix;
        }

        $this->translatedProperties = new MultipleTranslatedProperties(
            $this->properties, $languageNamespace, implode('-', $namespaces)
        );
        $this->translatedProperties->setLanguage($languageCode);
    }

    /**
     * returns data of extension
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * returns name of extension
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

} 
