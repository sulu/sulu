<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata;

use Sulu\Component\Rest\ListBuilder\FieldDescriptorInterface;
use Sulu\Component\Util\XmlUtil;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Parses data from xml and returns general list-builder metadata.
 */
class ListXmlLoader
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function load($resource)
    {
        $listMetadata = new ListMetadata();

        $cwd = \getcwd();
        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @\chdir(\dirname($resource));

        $xmlDocument = XmlUtils::loadFile(
            $resource,
            function(\DOMDocument $dom) use ($resource) {
                $dom->documentURI = $resource;
                $dom->xinclude();

                return @$dom->schemaValidate(__DIR__ . '/Resources/schema/list-2.0.xsd');
            }
        );

        // Necessary only for Windows, no effect on linux. Mute errors for PHP with chdir disabled to avoid E_WARNINGs
        @\chdir($cwd);

        $xpath = new \DOMXPath($xmlDocument);
        $xpath->registerNamespace('x', 'http://schemas.sulu.io/list-builder/list');

        $listMetadata->setResource($resource);
        $listMetadata->setKey($xpath->query('/x:list/x:key')->item(0)->nodeValue);

        foreach ($xpath->query('/x:list/x:properties/x:*') as $propertyNode) {
            $listMetadata->addPropertyMetadata($this->loadPropertyMetadata($xpath, $propertyNode));
        }

        return $listMetadata;
    }

    /**
     * Extracts attributes from dom-node to create a new property-metadata object.
     *
     * @return AbstractPropertyMetadata
     */
    private function loadPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $propertyMetadata = null;
        $propertyMetadata = match ($propertyNode->nodeName) {
            'concatenation-property' => $this->loadConcatenationPropertyMetadata($xpath, $propertyNode),
            'identity-property' => $this->loadIdentityPropertyMetadata($xpath, $propertyNode),
            'group-concat-property' => $this->loadGroupConcatPropertyMetadata($xpath, $propertyNode),
            'case-property' => $this->loadCasePropertyMetadata($xpath, $propertyNode),
            'count-property' => $this->loadCountPropertyMetadata($xpath, $propertyNode),
            'property' => $this->loadSinglePropertyMetadata($xpath, $propertyNode),
            default => throw new \InvalidArgumentException(\sprintf(
                'The tag "%s" cannot be handled by this loader',
                $propertyNode->nodeName
            )),
        };

        if (null !== $translation = XmlUtil::getValueFromXPath('@translation', $xpath, $propertyNode)) {
            $propertyMetadata->setTranslation($translation);
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

        $propertyMetadata->setWidth(
            XmlUtil::getValueFromXPath(
                '@width',
                $xpath,
                $propertyNode,
                FieldDescriptorInterface::WIDTH_AUTO
            )
        );

        if (null !== $type = XmlUtil::getValueFromXPath('x:transformer/@type', $xpath, $propertyNode)) {
            $propertyMetadata->setType($type);
        } elseif (null !== $type = XmlUtil::getValueFromXPath('@type', $xpath, $propertyNode)) {
            @trigger_deprecation('sulu/sulu', '2.2', 'Attribute "type" of list property should not be used anymore! Use "<transformer type="..."/>" inside of property instead.');
            $propertyMetadata->setType($type);
        }

        $transformerParamNodes = $xpath->query('x:transformer/x:params', $propertyNode);
        if (\count($transformerParamNodes) > 0) {
            $propertyMetadata->setTransformerTypeParameters(
                $this->getParameters(
                    $xpath,
                    $transformerParamNodes->item(0) // There can only be one transformer node
                )
            );
        }

        $propertyMetadata->setFilterType((string) XmlUtil::getValueFromXPath('x:filter/@type', $xpath, $propertyNode));

        $filterParamNodes = $xpath->query('x:filter/x:params', $propertyNode);
        if (\count($filterParamNodes) > 0) {
            $propertyMetadata->setFilterTypeParameters(
                $this->getParameters(
                    $xpath,
                    $filterParamNodes->item(0) // There can only be one filter node
                )
            );
        }

        return $propertyMetadata;
    }

    private function loadIdentityPropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new IdentityPropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);

        return $propertyMetadata;
    }

    private function loadCasePropertyMetadata(\DOMXPath $xpath, \DOMElement $propertyNode)
    {
        $propertyMetadata = new CasePropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
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

        $propertyMetadata = new GroupConcatPropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);
        $propertyMetadata->setGlue((string) XmlUtil::getValueFromXPath('@glue', $xpath, $propertyNode, ' '));
        $propertyMetadata->setDistinct(XmlUtil::getBooleanValueFromXPath('@distinct', $xpath, $propertyNode) ?? false);

        return $propertyMetadata;
    }

    private function loadSinglePropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new SinglePropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);

        return $propertyMetadata;
    }

    private function loadCountPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $field = $this->getField($xpath, $propertyNode);

        $propertyMetadata = new CountPropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setField($field);
        $propertyMetadata->setDistinct(XmlUtil::getBooleanValueFromXPath('@distinct', $xpath, $propertyNode) ?? false);

        return $propertyMetadata;
    }

    private function loadConcatenationPropertyMetadata(\DOMXPath $xpath, \DOMNode $propertyNode)
    {
        $propertyMetadata = new ConcatenationPropertyMetadata(
            (string) XmlUtil::getValueFromXPath('@name', $xpath, $propertyNode)
        );

        $propertyMetadata->setGlue((string) (XmlUtil::getValueFromXPath('@glue', $xpath, $propertyNode) ?? ' '));

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
     * @return ?array
     */
    protected function getParameters(\DOMXPath $xpath, \DOMNode $filterNode)
    {
        $parameters = [];
        foreach ($xpath->query('x:param', $filterNode) as $paramNode) {
            $name = XmlUtil::getValueFromXPath('@name', $xpath, $paramNode);
            $type = XmlUtil::getValueFromXPath('@type', $xpath, $paramNode);

            if ('collection' === $type) {
                $parameters[$name] = $this->getParameters($xpath, $paramNode);
            } else {
                $value = $this->parameterBag->resolveValue(
                    \trim((string) XmlUtil::getValueFromXPath('@value', $xpath, $paramNode))
                );

                if (null === $name) {
                    $parameters[] = 'expression' === $type ? ['type' => $type, 'value' => $value] : $value;
                } else {
                    $parameters[$name] = 'expression' === $type ? ['type' => $type, 'value' => $value] : $value;
                }
            }
        }

        if (\count($parameters) > 0) {
            return $parameters;
        }

        return null;
    }

    private function getField(\DOMXPath $xpath, \DOMElement $fieldNode)
    {
        if (null !== $reference = XmlUtil::getValueFromXPath('@property-ref', $xpath, $fieldNode)) {
            $nodeList = $xpath->query(\sprintf('/x:list/x:properties/x:*[@name="%s"]', $reference));

            if (0 === $nodeList->length) {
                throw new \Exception(\sprintf('Rest metadata doctrine field reference "%s" was not found.', $reference));
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
            $nodeList = $xpath->query(\sprintf('/x:list/x:joins[@name="%s"]', $reference));

            if (0 === $nodeList->length) {
                throw new \Exception(\sprintf('Rest metadata doctrine joins reference "%s" was not found.', $reference));
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
            /** @var 'ON'|'WITH' $conditionMethod */
            $joinMetadata->setConditionMethod($this->resolveParameter($conditionMethod));
        }

        if (null !== $method = XmlUtil::getValueFromXPath('x:method', $xpath, $joinNode)) {
            /** @var 'LEFT'|'INNER'|'RIGHT' $method */
            $joinMetadata->setMethod($method);
        }

        return $joinMetadata;
    }

    private function resolveParameter($value)
    {
        return $this->parameterBag->resolveValue($value);
    }
}
