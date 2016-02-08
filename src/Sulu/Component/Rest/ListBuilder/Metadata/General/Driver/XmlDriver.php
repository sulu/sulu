<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\General\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;

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
        if (($translation = XmlUtil::getValueFromXPath('@translation', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setTranslation($translation);
        }

        if (($disabled = XmlUtil::getValueFromXPath('@disabled', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setDisabled($disabled);
        }

        if (($default = XmlUtil::getValueFromXPath('@default', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setDefault($default);
        }

        if (($type = XmlUtil::getValueFromXPath('@type', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setType($type);
        }

        if (($width = XmlUtil::getValueFromXPath('@width', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setWith($width);
        }

        if (($minWidth = XmlUtil::getValueFromXPath('@min-width', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setMinWidth($minWidth);
        }

        if (($sortable = XmlUtil::getValueFromXPath('@sortable', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setSortable($sortable);
        }

        if (($editable = XmlUtil::getValueFromXPath('@editable', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setEditable($editable);
        }

        if (($cssClass = XmlUtil::getValueFromXPath('@css-class', $xpath, $propertyNode)) !== null) {
            $propertyMetadata->setCssClass($cssClass);
        }

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
