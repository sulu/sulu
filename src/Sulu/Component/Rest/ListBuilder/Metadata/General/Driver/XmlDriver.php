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

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\DatagridMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\FieldMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\JoinMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CaseTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\ConcatenationTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\CountTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\GroupConcatTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\IdentityTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\Doctrine\Type\SingleTypeMetadata;
use Sulu\Component\Rest\ListBuilder\Metadata\General\PropertyMetadata;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Parses data from xml and returns general list-builder metadata.
 */
class XmlDriver
{
    const SCHEME_PATH = '/../../Resources/schema/metadata/list-builder-general-1.0.xsd';

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function load($resource)
    {
        $datagridMetadata = new DatagridMetadata();

        // load xml file
        // TODO xsd validation
        $xmlDoc = XmlUtils::loadFile($resource);
        $xpath = new \DOMXPath($xmlDoc);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/class/general');

        $datagridMetadata->setResource($resource);
        $datagridMetadata->setKey($xpath->query('/x:datagrid/x:key')->item(0)->nodeValue);

        foreach ($xpath->query('/x:datagrid/x:properties/x:*') as $propertyNode) {
            $datagridMetadata->addPropertyMetadata($this->loadPropertyMetadata($xpath, $propertyNode));
        }

        return $datagridMetadata;
    }

    /**
     * Extracts attributes from dom-node to create a new property-metadata object.
     *
     * @param \DOMXPath $xpath
     * @param \DOMNode $propertyNode
     *
     * @return PropertyMetadata
     */
    private function loadPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $propertyMetadata = null;
        switch ($propertyNode->nodeName) {
            case 'concatenation-property':
                $propertyMetadata = $this->loadConcatenationPropertyMetadata($xpath, $propertyNode);
                break;
            case 'identity-property':
                $propertyMetadata = $this->loadIdentityPropertyMetadata($xpath, $propertyNode);
                break;
            case 'group-concat-property':
                $propertyMetadata = $this->loadGroupConcatPropertyMetadata($xpath, $propertyNode);
                break;
            case 'case-property':
                $propertyMetadata = $this->loadCasePropertyMetadata($xpath, $propertyNode);
                break;
            case 'count-property':
                $propertyMetadata = $this->loadCountPropertyMetadata($xpath, $propertyNode);
                break;
            case 'property':
                $propertyMetadata = $this->loadSinglePropertyMetadata($xpath, $propertyNode);
                break;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'The tag "%s" cannot be handled by this loader',
                    $propertyNode->nodeName
                ));
        }

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

        $propertyMetadata->setVisibility(
            XmlUtil::getValueFromXPath(
                '@visibility',
                $xpath,
                $propertyNode,
                FieldDescriptorInterface::VISIBILITY_NO
            )
        );
        $propertyMetadata->setSearchability(
            XmlUtil::getValueFromXPath(
                '@searchability',
                $xpath,
                $propertyNode,
                FieldDescriptorInterface::SEARCHABILITY_NEVER
            )
        );
        $propertyMetadata->setSortable(
            XmlUtil::getBooleanValueFromXPath('@sortable', $xpath, $propertyNode, true)
        );
        $propertyMetadata->setEditable(
            XmlUtil::getBooleanValueFromXPath('@editable', $xpath, $propertyNode, false)
        );
        $propertyMetadata->setFilterType(XmlUtil::getValueFromXPath('@filter-type', $xpath, $propertyNode));
        $propertyMetadata->setFilterTypeParameters($this->getFilterTypeParameters($xpath, $propertyNode));

        return $propertyMetadata;
    }

    private function loadIdentityPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new IdentityTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);

        return $propertyMetadata;
    }

    private function loadCasePropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $propertyMetadata = new CaseTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        foreach ($xpath->query('x:field', $propertyNode) as $fieldNode) {
            if (null === $case = $this->getField($xpath, $fieldNode)) {
                continue;
            }

            $propertyMetadata->addCase($case);
        }

        return $propertyMetadata;
    }

    private function loadGroupConcatPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new GroupConcatTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);
        $propertyMetadata->setGlue(XmlUtil::getValueFromXPath('@glue', $xpath, $propertyNode, ' '));
        $propertyMetadata->setDistinct(XmlUtil::getBooleanValueFromXPath('@distinct', $xpath, $propertyNode, false));

        return $propertyMetadata;
    }

    private function loadSinglePropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new SingleTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);

        return $propertyMetadata;
    }

    private function loadCountPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new CountTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);

        return $propertyMetadata;
    }

    private function loadConcatenationPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $propertyMetadata = new ConcatenationTypeMetadata(
            XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setGlue(XmlUtil::getValueFromXPath('@glue', $xpath, $propertyNode, ' '));

        foreach ($xpath->query('x:field', $propertyNode) as $fieldNode) {
            if (null === $field = $this->getField($xpath, $fieldNode)) {
                continue;
            }

            $propertyMetadata->addField($field);
        }

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

    private function getField(\DOMXPath $xpath, \DOMElement $fieldNode)
    {
        if (null !== $reference = XmlUtil::getValueFromXPath('@property-ref', $xpath, $fieldNode)) {
            $nodeList = $xpath->query(sprintf('/x:datagrid/x:properties/x:*[@name="%s"]', $reference));

            if (0 === $nodeList->length) {
                throw new \Exception(sprintf('Rest metadata doctrine field reference "%s" was not found.', $reference));
            }

            return $this->getField($xpath, $nodeList->item(0));
        }

        if (null === ($fieldName = XmlUtil::getValueFromXPath('x:field-name', $xpath, $fieldNode))
            || null === ($entityName = XmlUtil::getValueFromXPath('x:entity-name', $xpath, $fieldNode))
        ) {
            return;
        }

        $field = new FieldMetadata($this->resolveParameter($fieldName), $this->resolveParameter($entityName));

        $joinsNodeList = $xpath->query('x:joins', $fieldNode);
        if ($joinsNodeList->length > 0) {
            $this->getJoinsMetadata($xpath, $joinsNodeList->item(0), $field);
        }

        return $field;
    }

    private function getJoinsMetadata(\DOMXPath $xpath, \DOMElement $joinsNode, FieldMetadata $field)
    {
        if (null !== $reference = XmlUtil::getValueFromXPath('@ref', $xpath, $joinsNode)) {
            $nodeList = $xpath->query(sprintf('/x:datagrid/x:joins[@name="%s"]', $reference));

            if (0 === $nodeList->length) {
                throw new \Exception(sprintf('Rest metadata doctrine joins reference "%s" was not found.', $reference));
            }

            $this->getJoinsMetadata($xpath, $nodeList->item(0), $field);
        }

        foreach ($xpath->query('x:join', $joinsNode) as $joinNode) {
            $field->addJoin($this->getJoinMetadata($xpath, $joinNode));
        }
    }

    protected function getJoinMetadata(\DOMXPath $xpath, \DOMElement $joinNode)
    {
        $joinMetadata = new JoinMetadata();

        if (null !== $fieldName = XmlUtil::getValueFromXPath('x:field-name', $xpath, $joinNode)) {
            $joinMetadata->setEntityField($this->resolveParameter($fieldName));
        }

        if (null !== $entityName = XmlUtil::getValueFromXPath('x:entity-name', $xpath, $joinNode)) {
            $joinMetadata->setEntityName($this->resolveParameter($entityName));
        }

        if (null !== $condition = XmlUtil::getValueFromXPath('x:condition', $xpath, $joinNode)) {
            $joinMetadata->setCondition($this->resolveParameter($condition));
        }

        if (null !== $conditionMethod = XmlUtil::getValueFromXPath('x:condition-method', $xpath, $joinNode)) {
            $joinMetadata->setConditionMethod($this->resolveParameter($conditionMethod));
        }

        if (null !== $method = XmlUtil::getValueFromXPath('x:method', $xpath, $joinNode)) {
            $joinMetadata->setMethod($method);
        }

        return $joinMetadata;
    }

    private function resolveParameter($value)
    {
        return $this->parameterBag->resolveValue($value);
    }
}
