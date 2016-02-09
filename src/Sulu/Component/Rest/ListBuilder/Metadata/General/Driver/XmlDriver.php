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
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Parses data from xml and returns doctrine-metadata.
 */
class XmlDriver extends AbstractFileDriver implements DriverInterface
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/general-1.0.xsd';

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
        if (null !== $translation = XmlUtil::getValueFromXPath('@translation', $xpath, $propertyNode)) {
            $propertyMetadata->setTranslation($translation);
        }

        if (null !== $type = XmlUtil::getValueFromXPath('@type', $xpath, $propertyNode)) {
            $propertyMetadata->setType($type);
        }

        if (null !== $width = XmlUtil::getValueFromXPath('@width', $xpath, $propertyNode)) {
            $propertyMetadata->setWidth($width);
        }

        if (null !== $minWidth = XmlUtil::getValueFromXPath('@min-width', $xpath, $propertyNode)) {
            $propertyMetadata->setMinWidth($minWidth);
        }

        if (null !== $cssClass = XmlUtil::getValueFromXPath('@css-class', $xpath, $propertyNode)) {
            $propertyMetadata->setCssClass($cssClass);
        }

        $propertyMetadata->setDisabled(XmlUtil::getBooleanValueFromXPath('@disabled', $xpath, $propertyNode, false));
        $propertyMetadata->setDefault(XmlUtil::getBooleanValueFromXPath('@default', $xpath, $propertyNode, false));
        $propertyMetadata->setSortable(XmlUtil::getBooleanValueFromXPath('@sortable', $xpath, $propertyNode, true));
        $propertyMetadata->setEditable(XmlUtil::getBooleanValueFromXPath('@editable', $xpath, $propertyNode, false));

        return $propertyMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return 'xml';
    }
}
