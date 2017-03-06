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
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CaseTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CountTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\IdentityTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Parses data from xml and returns list-builder metadata for doctrine entities.
 */
class XmlDriver extends AbstractFileDriver implements DriverInterface
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/list-builder-doctrine-1.0.xsd';

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
        if (null === $type = $this->getType($xpath, $propertyNode)) {
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
     * @return null|CaseTypeMetadata|ConcatenationTypeMetadata|CountTypeMetadata|GroupConcatTypeMetadata|IdentityTypeMetadata|SingleTypeMetadata
     */
    protected function getType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        switch ($propertyNode->nodeName) {
            case 'concatenation-property':
                return $this->getConcatenationType($xpath, $propertyNode);
            case 'group-concat-property':
                return $this->getGroupConcatenationType($xpath, $propertyNode);
            case 'identity-property':
                return $this->getIdentityType($xpath, $propertyNode);
            case 'case-property':
                return $this->getCaseType($xpath, $propertyNode);
            case 'count-property':
                return $this->getCountType($xpath, $propertyNode);
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
     * @return SingleTypeMetadata
     */
    protected function getSingleType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        if (null === $field = $this->getField($xpath, $propertyNode)) {
            return;
        }

        return new SingleTypeMetadata($field);
    }

    /**
     * Extracts concatenation-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return ConcatenationTypeMetadata
     */
    protected function getConcatenationType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $type = new ConcatenationTypeMetadata(XmlUtil::getValueFromXPath('@orm:glue', $xpath, $propertyNode, ' '));
        foreach ($xpath->query('orm:field', $propertyNode) as $fieldNode) {
            if (null === $field = $this->getField($xpath, $fieldNode)) {
                continue;
            }

            $type->addField($field);
        }

        return $type;
    }

    /**
     * Extracts group-concatenation-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return GroupConcatTypeMetadata
     */
    protected function getGroupConcatenationType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        if (null === $field = $this->getField($xpath, $propertyNode)) {
            return;
        }

        return new GroupConcatTypeMetadata(
            $field,
            XmlUtil::getValueFromXPath('@orm:glue', $xpath, $propertyNode, ' '),
            XmlUtil::getBooleanValueFromXPath('@orm:distinct', $xpath, $propertyNode, false)
        );
    }

    /**
     * Extracts identity-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return IdentityTypeMetadata
     */
    protected function getIdentityType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        if (null === $field = $this->getField($xpath, $propertyNode)) {
            return;
        }

        return new IdentityTypeMetadata($field);
    }

    /**
     * Extracts case-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return CaseTypeMetadata
     */
    protected function getCaseType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $type = new CaseTypeMetadata();
        foreach ($xpath->query('orm:field', $propertyNode) as $fieldNode) {
            if (null === $case = $this->getField($xpath, $fieldNode)) {
                continue;
            }

            $type->addCase($case);
        }

        return $type;
    }

    /**
     * Extracts count-type for property-node.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $propertyNode
     *
     * @return CountTypeMetadata
     */
    protected function getCountType(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        if (null === $field = $this->getField($xpath, $propertyNode)) {
            return;
        }

        return new CountTypeMetadata($field);
    }

    /**
     * Extracts data from dom-node to create a new field object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMElement $fieldNode
     *
     * @return FieldMetadata
     *
     * @throws \Exception
     */
    protected function getField(\DOMXPath $xpath, \DOMElement $fieldNode)
    {
        if (null !== $reference = XmlUtil::getValueFromXPath('@property-ref', $xpath, $fieldNode)) {
            $nodeList = $xpath->query(sprintf('/x:class/x:properties/x:*[@name="%s"]', $reference));

            if ($nodeList->length === 0) {
                throw new \Exception(sprintf('Rest metadata doctrine field reference "%s" was not found.', $reference));
            }

            return $this->getField($xpath, $nodeList->item(0));
        }

        if (null === ($fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $fieldNode))
            || null === ($entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $fieldNode))
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
     *
     * @throws \Exception
     */
    protected function getJoinsMetadata(\DOMXPath $xpath, \DOMElement $joinsNode, FieldMetadata $field)
    {
        if (null !== $reference = XmlUtil::getValueFromXPath('@ref', $xpath, $joinsNode)) {
            $nodeList = $xpath->query(sprintf('/x:class/orm:joins[@name="%s"]', $reference));

            if ($nodeList->length === 0) {
                throw new \Exception(sprintf('Rest metadata doctrine joins reference "%s" was not found.', $reference));
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

        if (null !== $fieldName = XmlUtil::getValueFromXPath('orm:field-name', $xpath, $joinNode)) {
            $joinMetadata->setEntityField($this->resolveParameter($fieldName));
        }

        if (null !== $entityName = XmlUtil::getValueFromXPath('orm:entity-name', $xpath, $joinNode)) {
            $joinMetadata->setEntityName($this->resolveParameter($entityName));
        }

        if (null !== $condition = XmlUtil::getValueFromXPath('orm:condition', $xpath, $joinNode)) {
            $joinMetadata->setCondition($this->resolveParameter($condition));
        }

        if (null !== $conditionMethod = XmlUtil::getValueFromXPath('orm:condition-method', $xpath, $joinNode)) {
            $joinMetadata->setConditionMethod($this->resolveParameter($conditionMethod));
        }

        if (null !== $method = XmlUtil::getValueFromXPath('orm:method', $xpath, $joinNode)) {
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
