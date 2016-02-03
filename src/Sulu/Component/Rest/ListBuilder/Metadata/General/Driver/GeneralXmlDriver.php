<?php

namespace Sulu\Component\Rest\ListBuilder\Metadata\General\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\ConcatenationPropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyReferenceMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;

class GeneralXmlDriver extends AbstractFileDriver implements DriverInterface
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
            $propertyMetadata = null;
            switch ($propertyNode->nodeName) {
                case 'concatenation-property':
                    $propertyMetadata = $this->getConcatenationPropertyMetadata($xpath, $propertyNode, $class->getName());
                    break;
                case 'property-ref':
                    $propertyMetadata = $this->getReferencePropertyMetadata($xpath, $propertyNode, $class->getName());
                    break;
                default:
                    $propertyMetadata = $this->getPropertyMetadata($xpath, $propertyNode, $class->getName());
                    break;
            }
            $classMetadata->addPropertyMetadata($propertyMetadata);
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
     * Extracts data from dom-node to create a new reference-property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     * @param string $className
     *
     * @return PropertyMetadata
     */
    protected function getReferencePropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode, $className)
    {
        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);

        return $this->setDefaultData(new PropertyReferenceMetadata($className, $name), $xpath, $propertyNode);
    }

    /**
     * Extracts attributes from dom-node to create a new concatenation-property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     * @param string $className
     *
     * @return PropertyMetadata
     */
    protected function getConcatenationPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode, $className)
    {
        $name = XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode);
        $propertyMetadata = new ConcatenationPropertyMetadata($className, $name);

        foreach ($xpath->query('x:*', $propertyNode) as $childPropertyNode) {
            $childPropertyMetadata = null;
            switch ($childPropertyNode->nodeName) {
                case 'property-ref':
                    $childPropertyMetadata = $this->getReferencePropertyMetadata($xpath, $childPropertyNode, $className);
                    break;
                default:
                    $childPropertyMetadata = $this->getPropertyMetadata($xpath, $childPropertyNode, $className);
                    break;
            }
            $propertyMetadata->addPropertyMetadata($childPropertyMetadata);
        }

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
