<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Driver;

use Metadata\Driver\AbstractFileDriver;
use Metadata\Driver\DriverInterface;
use Metadata\Driver\FileLocatorInterface;
use Metadata\MergeableClassMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\PropertyMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationType;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleType;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Parses data from xml and returns doctrine-metadata.
 */
class XmlDriver extends AbstractFileDriver implements DriverInterface
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/general-1.0.xsd';

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
        $xpath->registerNamespace('orm', 'http://schemas.sulu.io/class/doctrine');

        foreach ($xpath->query('/x:class/x:properties/x:*') as $propertyNode) {
            $propertyMetadata = $this->getPropertyMetadata($xpath, $propertyNode, $class->getName());
            if ($propertyMetadata !== null) {
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
        if (($type = $this->getType($xpath, $propertyNode)) === null) {
            return;
        }

        return new PropertyMetadata($className, XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode), $type);
    }

    /**
     * Extracts type from property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return ConcatenationType|SingleType
     */
    protected function getType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        switch ($propertyNode->nodeName) {
            case 'concatenation-property':
                return $this->getConcatenationType($xpath, $propertyNode);
            default:
                return $this->getSingleType($xpath, $propertyNode);
        }
    }

    /**
     * Extracts single-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return SingleType
     */
    protected function getSingleType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        if (($field = $this->getField($xpath, $propertyNode)) === null) {
            return;
        }

        return new SingleType($field);
    }

    /**
     * Extracts concatenation-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return SingleType
     */
    protected function getConcatenationType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $type = new ConcatenationType(XmlUtil::getValueFromXPath('@orm:glue', $xpath, $propertyNode, ' '));
        foreach ($xpath->query('orm:field', $propertyNode) as $fieldNode) {
            if (($field = $this->getField($xpath, $fieldNode)) === null) {
                continue;
            }

            $type->addField($field);
        }

        return $type;
    }

    /**
     * Extracts data from dom-node to create a new field object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $fieldNode
     *
     * @return FieldMetadata
     */
    protected function getField(\DOMXPath $xpath, \DOMElement $fieldNode)
    {
        if (($reference = XmlUtil::getValueFromXPath('@property-ref', $xpath, $fieldNode)) !== null) {
            $nodeList = $xpath->query(sprintf('/x:class/x:properties/x:*[@name="%s"]', $reference));

            if ($nodeList->length === 0) {
                return;
            }

            return $this->getField($xpath, $nodeList->item(0));
        }

        if (($fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $fieldNode)) === null
            || ($entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $fieldNode)) === null
        ) {
            return;
        }

        $field = new FieldMetadata($this->resolveParameter($fieldName), $this->resolveParameter($entityName));

        $joinsNodeList = $xpath->query('orm:joins', $fieldNode);
        if ($joinsNodeList->length > 0) {
            $this->getJoinsMetadata($xpath, $joinsNodeList->item(0), $field);
        }

        return $field;
    }

    /**
     * Extracts data from dom-node to create all join-metadata.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $joinsNode
     * @param FieldMetadata $field
     */
    protected function getJoinsMetadata(\DOMXPath $xpath, \DOMElement $joinsNode, FieldMetadata $field)
    {
        if (($reference = XmlUtil::getValueFromXPath('@ref', $xpath, $joinsNode)) !== null) {
            $nodeList = $xpath->query(sprintf('/x:class/orm:joins[@name="%s"]', $reference));

            if ($nodeList->length === 0) {
                return;
            }

            $this->getJoinsMetadata($xpath, $nodeList->item(0), $field);
        }

        foreach ($xpath->query('orm:join', $joinsNode) as $joinNode) {
            $field->addJoin($this->getJoinMetadata($xpath, $joinNode));
        }
    }

    /**
     * Extracts data from dom-node to create a new join-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $joinNode
     *
     * @return JoinMetadata
     */
    protected function getJoinMetadata(\DOMXPath $xpath, \DOMElement $joinNode)
    {
        $joinMetadata = new JoinMetadata();

        if (($fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $joinNode)) !== null) {
            $joinMetadata->setEntityField($this->resolveParameter($fieldName));
        }

        if (($entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $joinNode)) !== null) {
            $joinMetadata->setEntityName($this->resolveParameter($entityName));
        }

        if (($condition = XmlUtil::getValueFromXPath('orm:condition', $xpath, $joinNode)) !== null) {
            $joinMetadata->setCondition($condition);
        }

        if (($conditionMethod = XmlUtil::getValueFromXPath('orm:condition-method', $xpath, $joinNode)) !== null) {
            $joinMetadata->setConditionMethod($conditionMethod);
        }

        if (($method = XmlUtil::getValueFromXPath('orm:method', $xpath, $joinNode)) !== null) {
            $joinMetadata->setMethod($method);
        }

        return $joinMetadata;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function resolveParameter($value)
    {
        return $this->parameterBag->resolveValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtension()
    {
        return 'xml';
    }
}
