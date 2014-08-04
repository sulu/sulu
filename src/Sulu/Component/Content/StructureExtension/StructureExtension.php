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

use PHPCR\NodeInterface;
use Sulu\Component\Content\Mapper\Translation\MultipleTranslatedProperties;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

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

    /**
     * save a single property value
     * @param NodeInterface $node
     * @param array $data data array
     * @param string $name name of property in node an data array
     * @param string $default value if no data exists with given name
     * @param string $default
     */
    protected function saveProperty(NodeInterface $node, $data, $name, $default = '')
    {
        $value = isset($data[$name]) ? $data[$name] : $default;
        $node->setProperty($this->getPropertyName($name), $value);
    }

    /**
     * load a single property value
     * @param NodeInterface $node
     * @param string $name name of property in node
     * @param string $default value if no property exists with given name
     * @return mixed
     */
    protected function loadProperty(NodeInterface $node, $name, $default = '')
    {
        return $node->getPropertyValueWithDefault($this->getPropertyName($name), $default);
    }

    /**
     * Returns value of given property name
     * @param string $name property name
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @return mixed
     */
    function __get($name)
    {
        if ($this->__isset($name)) {
            return $this->data[$name];
        } else {
            throw new NoSuchPropertyException(
                sprintf('Property "%s" not exists in extension "%s"', $name, $this->getName())
            );
        }
    }

    /**
     * indicates that property exists
     * @param $name
     * @return bool
     */
    function __isset($name)
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        } else {
            return array_key_exists($name, $this->data);
        }
    }

} 
