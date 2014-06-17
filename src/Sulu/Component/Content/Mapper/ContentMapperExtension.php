<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

/**
 * basic implementation of content mapper extension
 * @package Sulu\Component\Content\Mapper
 */
abstract class ContentMapperExtension implements ContentMapperExtensionInterface
{

    /**
     * @var string[]
     */
    protected $properties;

    protected $additionalPrefix;

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

} 
