<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\General\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\FileLocatorInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Parses data from xml and returns general list-builder metadata.
 */
class XmlDriver extends AbstractFileDriver implements DriverInterface
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/list-builder-general-1.0.xsd';

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(FileLocatorInterface $locator, ParameterBagInterface $parameterBag)
    {
        parent::__construct($locator);

        $this->parameterBag = $parameterBag;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadMetadataFromFile(\ReflectionClass $class, $file)
    {
        $classMetadata = new MergeableClassMetadata($class->getName());

        // load xml file
        // TODO xsd validation
        $xmlDoc = XmlUtils::loadFile($file);
        $xpath = new \DOMXPath($xmlDoc);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/class/general');
        $xpath->registerNamespace('list', 'http://schemas.sulu.io/class/list');

        foreach ($xpath->query('/x:class/x:properties/x:*') as $propertyNode) {
            $classMetadata->addPropertyMetadata($this->getPropertyMetadata($xpath, $propertyNode, $class->getName()));
        }

        return $classMetadata;
    }

    /**
     * Extracts attributes from dom-node to create a new property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     * @param string $className
     *
     * @return PropertyMetadata
     */
    protected function getPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode, $className)
    {
        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);
        $propertyMetadata = new PropertyMetadata($className, $name);

        return $this->setDefaultData($propertyMetadata, $xpath, $propertyNode);
    }

    /**
     * Set default data onto the property-metadata.
     *
     * @param PropertyMetadata $propertyMetadata
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     *
     * @return PropertyMetadata
     */
    protected function setDefaultData(PropertyMetadata $propertyMetadata, \DOMXPath $xpath, \DOMNode $propertyNode)
    {
        if (null !== $translation = XmlUtil::getValueFromXPath('@list:translation', $xpath, $propertyNode)) {
            $propertyMetadata->setTranslation($translation);
        }

        if (null !== $type = XmlUtil::getValueFromXPath('@list:type', $xpath, $propertyNode)) {
            $propertyMetadata->setType($type);
        }

        if (null !== $width = XmlUtil::getValueFromXPath('@list:width', $xpath, $propertyNode)) {
            $propertyMetadata->setWidth($width);
        }

        if (null !== $minWidth = XmlUtil::getValueFromXPath('@list:min-width', $xpath, $propertyNode)) {
            $propertyMetadata->setMinWidth($minWidth);
        }

        if (null !== $cssClass = XmlUtil::getValueFromXPath('@list:css-class', $xpath, $propertyNode)) {
            $propertyMetadata->setCssClass($cssClass);
        }

        $propertyMetadata->setDisplay(
            XmlUtil::getValueFromXPath('@display', $xpath, $propertyNode, PropertyMetadata::DISPLAY_NO)
        );
        $propertyMetadata->setSortable(
            XmlUtil::getBooleanValueFromXPath('@list:sortable', $xpath, $propertyNode, true)
        );
        $propertyMetadata->setEditable(
            XmlUtil::getBooleanValueFromXPath('@list:editable', $xpath, $propertyNode, false)
        );
        $propertyMetadata->setFilterType(XmlUtil::getValueFromXPath('@filter-type', $xpath, $propertyNode));
        $propertyMetadata->setFilterTypeParameters($this->getFilterTypeParameters($xpath, $propertyNode));

        return $propertyMetadata;
    }

    /**
     * Extracts filter type parameters from dom-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     *
     * @return array
     */
    protected function getFilterTypeParameters(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $parameters = [];
        foreach ($xpath->query('x:filter-type-parameters/x:parameter', $propertyNode) as $parameterNode) {
            $key = XmlUtil::getValueFromXPath('@key', $xpath, $parameterNode);
            $parameters[$key] = $this->parameterBag->resolveValue(trim($parameterNode->nodeValue));
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return 'xml';
    }
}
