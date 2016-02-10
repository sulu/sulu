<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Resource\Metadata\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Bundle\ResourceBundle\Resource\Metadata\PropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * Parses data from xml and returns filter-metadata.
 */
class XmlDriver extends AbstractFileDriver implements DriverInterface
{
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
        $xpath->registerNamespace('filter', 'http://schemas.sulu.io/class/filter');

        foreach ($xpath->query('/x:class/x:properties/x:*') as $propertyNode) {
            $propertyMetadata = $this->getPropertyMetadata($xpath, $propertyNode, $class->getName());
            if (null !== $propertyMetadata) {
                $classMetadata->addPropertyMetadata($propertyMetadata);
            }
        }

        return $classMetadata;
    }

    /**
     * Extracts data from dom-node to create a new property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     * @param string $className
     *
     * @return PropertyMetadata
     */
    protected function getPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode, $className)
    {
        if (null === $inputType = XmlUtil::getValueFromXPath('@filter:input-type', $xpath, $propertyNode)) {
            return;
        }

        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);

        return new PropertyMetadata($className, $name, $inputType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtension()
    {
        return 'xml';
    }
}
