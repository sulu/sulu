<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Extension;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;

/**
 * basic implementation of content mapper extension.
 */
abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * @var string[]
     */
    protected $properties;

    /**
     * additional prefix for properties.
     *
     * @var string
     */
    protected $additionalPrefix;

    /**
     * name of extension.
     *
     * @var string
     */
    protected $name;

    /**
     * @var MultipleTranslatedProperties
     */
    private $translatedProperties;

    /**
     * returns translated property name.
     *
     * @param $propertyName
     *
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
        $namespaces = [];
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * save a single property value.
     *
     * @param NodeInterface $node
     * @param array         $data    data array
     * @param string        $name    name of property in node an data array
     * @param string        $default value if no data exists with given name
     * @param string        $default
     */
    protected function saveProperty(NodeInterface $node, $data, $name, $default = '')
    {
        $value = isset($data[$name]) ? $data[$name] : $default;
        $node->setProperty($this->getPropertyName($name), $value);
    }

    /**
     * load a single property value.
     *
     * @param NodeInterface $node
     * @param string        $name    name of property in node
     * @param string        $default value if no property exists with given name
     *
     * @return mixed
     */
    protected function loadProperty(NodeInterface $node, $name, $default = '')
    {
        return $node->getPropertyValueWithDefault($this->getPropertyName($name), $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData($container)
    {
        return $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldMapping()
    {
        return [];
    }
}
