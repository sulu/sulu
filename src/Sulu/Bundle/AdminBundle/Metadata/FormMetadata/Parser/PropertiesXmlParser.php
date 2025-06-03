<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\XmlParserTrait;
use Sulu\Component\Content\Exception\InvalidDefaultTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class PropertiesXmlParser
{
    use XmlParserTrait;

    /**
     * @var string[]
     */
    private $locales;

    /**
     * @param array<string, string> $locales
     */
    public function __construct(
        private TagXmlParser $tagXmlParser,
        private TranslatorInterface $translator,
        array $locales,
    ) {
        $this->locales = \array_keys($locales);
    }

    /**
     * @return array<FieldMetadata|SectionMetadata>
     */
    public function load(
        \DOMXPath $xpath,
        \DOMNode $context,
        ?string $formKey = null
    ): array {
        $propertyData = $this->loadProperties($xpath, $context, $formKey);

        return $this->mapProperties($propertyData);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadProperties(\DOMXPath $xpath, \DOMNode $context, ?string $formKey): array
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query('x:*', $context) as $node) {
            if ('property' === $node->tagName) {
                $value = $this->loadProperty($xpath, $node, $formKey);
                $result[$value['name']] = $value;
            } elseif ('block' === $node->tagName) {
                $value = $this->loadBlock($xpath, $node, $formKey);
                $result[$value['name']] = $value;
            } elseif ('section' === $node->tagName) {
                $value = $this->loadSection($xpath, $node, $formKey);
                $result[$value['name']] = $value;
            }
        }

        return $result;
    }

    private function loadProperty(\DOMXPath $xpath, \DOMNode $node, ?string $formKey)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            [
                'name',
                'type',
                'default-type',
                'minOccurs',
                'maxOccurs',
                'colspan',
                'cssClass',
                'spaceAfter',
                'disabledCondition',
                'visibleCondition',
            ]
        );

        $result['mandatory'] = $this->getValueFromXPath('@mandatory', $xpath, $node, false);
        $result['multilingual'] = $this->getValueFromXPath('@multilingual', $xpath, $node, true);
        $result['onInvalid'] = $this->getValueFromXPath('@onInvalid', $xpath, $node);
        $result['tags'] = $this->loadTags($xpath, $node);
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta($xpath, $node);
        $result['types'] = $this->loadTypes($xpath, $node, $formKey);

        $typeNames = \array_map(function($type) {
            return $type['name'];
        }, $result['types']);

        if (!empty($typeNames)) {
            if (!$result['default-type'] && null !== ($key = \array_key_first($typeNames))) {
                $result['default-type'] = $typeNames[$key];
            }

            if (!\in_array($result['default-type'], $typeNames)) {
                throw new InvalidDefaultTypeException($result['name'], $result['default-type'], $typeNames);
            }
        }

        return $result;
    }

    private function loadBlock(\DOMXPath $xpath, \DOMNode $node, ?string $formKey)
    {
        $result = $this->loadProperty($xpath, $node, $formKey);
        $result['type'] = 'block';

        return $result;
    }

    private function loadSection(\DOMXPath $xpath, \DOMNode $node, ?string $formKey)
    {
        $result = $this->loadValues(
            $xpath,
            $node,
            ['name', 'colspan', 'cssClass', 'disabledCondition', 'visibleCondition']
        );

        $result['type'] = 'section';
        $result['params'] = $this->loadParams('x:params/x:param', $xpath, $node);
        $result['meta'] = $this->loadMeta($xpath, $node);

        $propertiesNode = $xpath->query('x:properties', $node)->item(0);
        $result['properties'] = $this->loadProperties($xpath, $propertiesNode, $formKey);

        return $result;
    }

    private function loadTags(\DOMXPath $xpath, ?\DOMNode $context = null)
    {
        return $this->tagXmlParser->load($xpath, $xpath->query('x:tag', $context));
    }

    private function loadTypes(\DOMXPath $xpath, ?\DOMNode $context, ?string $formKey)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query('x:types/x:type', $context) as $node) {
            $value = $this->loadType($xpath, $node, $formKey);
            $result[$value['name']] = $value;
        }

        return $result;
    }

    private function loadType(\DOMXPath $xpath, \DOMNode $node, ?string $formKey)
    {
        $result = $this->loadValues($xpath, $node, ['name', 'ref']);
        if ($result['ref'] && $result['name']) {
            throw new \InvalidArgumentException(\sprintf(
                "Element '{http://schemas.sulu.io/template/template}type', attribute 'name' / 'ref': The attribute 'name' and 'ref' is not allowed at the same time. (in %s - line %s)",
                $node->baseURI,
                $node->getLineNo()
            ));
        } elseif (!$result['ref'] && !$result['name']) {
            throw new \InvalidArgumentException(\sprintf(
                "Element '{http://schemas.sulu.io/template/template}type', attribute 'name' / 'ref': The attribute 'name' or 'ref' is required. (in %s - line %s)",
                $node->baseURI,
                $node->getLineNo()
            ));
        }

        if ($result['ref']) {
            $result['name'] = $result['ref'];
            $result['ref'] = true;
        }

        $result['meta'] = $this->loadMeta($xpath, $node);

        $propertiesNode = $xpath->query('x:properties', $node)->item(0);
        if ($propertiesNode) {
            $result['properties'] = $this->loadProperties($xpath, $propertiesNode, $formKey);
        }

        return $result;
    }

    private function loadValues(\DOMXPath $xpath, \DOMNode $node, $keys, $prefix = '@')
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->getValueFromXPath($prefix . $key, $xpath, $node);
        }

        return $result;
    }

    private function loadMeta(\DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];
        $metaNode = $xpath->query('x:meta', $context)->item(0);

        if (!$metaNode) {
            return $result;
        }

        $result['title'] = $this->loadMetaTag('x:title', $xpath, $metaNode);
        $result['info_text'] = $this->loadMetaTag('x:info_text', $xpath, $metaNode);
        $result['placeholder'] = $this->loadMetaTag('x:placeholder', $xpath, $metaNode);

        return $result;
    }

    private function loadMetaTag($path, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        $translationKey = null;

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $lang = $this->getValueFromXPath('@lang', $xpath, $node);

            if (!$lang) {
                $translationKey = $node->textContent;

                continue;
            }

            $result[$lang] = $node->textContent;
        }

        if (!$translationKey) {
            return $result;
        }

        $missingLocales = \array_diff($this->locales, \array_keys($result));
        foreach ($missingLocales as $missingLocale) {
            $result[$missingLocale] = $this->translator->trans($translationKey, [], 'admin', $missingLocale);
        }

        return $result;
    }

    private function loadParams($path, \DOMXPath $xpath, ?\DOMNode $context = null)
    {
        $result = [];

        /** @var \DOMElement $node */
        foreach ($xpath->query($path, $context) as $node) {
            $result[] = $this->loadParam($xpath, $node);
        }

        return $result;
    }

    private function loadParam(\DOMXPath $xpath, \DOMNode $node)
    {
        $result = [
            'name' => $this->getValueFromXPath('@name', $xpath, $node),
            'type' => $this->getValueFromXPath('@type', $xpath, $node, 'string'),
            'meta' => $this->loadMeta($xpath, $node),
        ];

        $result['value'] = match ($result['type']) {
            'collection' => $this->loadParams('x:param', $xpath, $node),
            default => $this->getValueFromXPath('@value', $xpath, $node),
        };

        return $result;
    }

    /**
     * @param array<string, array<string, mixed>> $data
     *
     * @return array<FieldMetadata|SectionMetadata>
     */
    private function mapProperties(array $data): array
    {
        $properties = [];
        foreach ($data as $propertyName => $dataProperty) {
            $property = $this->createProperty($propertyName, $dataProperty);

            if ($property) {
                $properties[] = $property;
            }
        }

        return $properties;
    }

    private function createProperty(string $propertyName, array $propertyData): FieldMetadata|SectionMetadata
    {
        if ('section' === $propertyData['type']) {
            return $this->createSection($propertyName, $propertyData);
        }

        $property = new FieldMetadata($propertyName);
        $this->mapProperty($property, $propertyData);

        return $property;
    }

    private function createSection($propertyName, $data): SectionMetadata
    {
        $section = new SectionMetadata($propertyName);
        if (isset($data['colspan'])) {
            $section->setColSpan($data['colspan']);
        }

        if (isset($data['meta']['title'])) {
            $section->setLabels($data['meta']['title']);
        }

        if (isset($data['meta']['info_text'])) {
            $section->setDescriptions($data['meta']['info_text']);
        }

        if (isset($data['disabledCondition'])) {
            $section->setDisabledCondition($this->normalizeConditionData($data['disabledCondition']));
        }

        if (isset($data['visibleCondition'])) {
            $section->setVisibleCondition($this->normalizeConditionData($data['visibleCondition']));
        }

        foreach ($data['properties'] as $name => $property) {
            $section->addItem($this->createProperty($name, $property));
        }

        return $section;
    }

    private function mapProperty(FieldMetadata $property, $data): void
    {
        $data = $this->normalizePropertyData($data);

        $property->setDefaultType($data['default-type']);
        $property->setType($data['type']);
        $property->setMultilingual($data['multilingual']);
        $property->setRequired($data['mandatory']);
        if (isset($data['colspan'])) {
            $property->setColSpan($data['colspan']);
        }
        $property->setSpaceAfter($data['spaceAfter']);
        $property->setTags($data['tags'] ?? []);
        $property->setMinOccurs(null !== $data['minOccurs'] ? \intval($data['minOccurs']) : null);
        $property->setMaxOccurs(null !== $data['maxOccurs'] ? \intval($data['maxOccurs']) : null);
        $property->setDisabledCondition($this->normalizeConditionData($data['disabledCondition'] ?? null));
        $property->setVisibleCondition($this->normalizeConditionData($data['visibleCondition'] ?? null));
        $property->setOnInvalid(\array_key_exists('onInvalid', $data) ? $data['onInvalid'] : null);

        // TODO schema

        foreach ($data['params'] as $parameter) {
            $option = new OptionMetadata();
            $option->setName($parameter['name']);
            $option->setType($parameter['type']);

            if (OptionMetadata::TYPE_COLLECTION === $parameter['type']) {
                foreach ($parameter['value'] as $parameterName => $parameterValue) {
                    $valueOption = new OptionMetadata();
                    $valueOption->setName($parameterValue['name']);
                    $valueOption->setValue($parameterValue['value']);

                    $this->mapOptionMeta($parameterValue, $valueOption);

                    $option->addValueOption($valueOption);
                }
            } elseif (OptionMetadata::TYPE_STRING === $parameter['type'] || OptionMetadata::TYPE_EXPRESSION === $parameter['type']) {
                $option->setValue($parameter['value']);
                $this->mapOptionMeta($parameter, $option);
            } else {
                throw new \Exception('Unsupported parameter given "' . \get_class($parameter) . '"');
            }

            $property->addOption($option);
        }

        $this->mapMeta($property, $data['meta']);

        $types = $data['types'];
        foreach ($types as $name => $typeData) {
            $type = new FormMetadata();
            $type->setKey($name);
            $type->setTitles($typeData['meta']['title'] ?? []);

            if (!$typeData['ref']) {
                foreach ($this->mapProperties($typeData['properties']) as $childProperty) {
                    $type->addItem($childProperty);
                }
            } else {
                $tagMetadata = new TagMetadata();
                $tagMetadata->setName('sulu.global_block');
                $tagMetadata->setAttributes([
                    'global_block' => $name,
                ]);

                $type->addTag($tagMetadata);
            }

            $property->addType($type);
        }
    }

    private function normalizePropertyData($data): array
    {
        $data = \array_replace_recursive(
            [
                'type' => null,
                'multilingual' => true,
                'mandatory' => true,
                'colspan' => null,
                'cssClass' => null,
                'minOccurs' => null,
                'maxOccurs' => null,
                'spaceAfter' => null,
            ],
            $this->normalizeItem($data)
        );

        return $data;
    }

    private function normalizeConditionData($data): ?string
    {
        if (\is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        return $data;
    }

    private function normalizeItem($data): array
    {
        $data = \array_merge_recursive(
            [
                'meta' => [
                    'title' => [],
                    'info_text' => [],
                    'placeholders' => [],
                ],
                'params' => [],
                'tags' => [],
            ],
            $data
        );

        return $data;
    }

    private function mapOptionMeta(array $parameterValue, OptionMetadata $option): void
    {
        if (!\array_key_exists('meta', $parameterValue)) {
            return;
        }

        foreach ($parameterValue['meta'] as $metaKey => $metaValues) {
            switch ($metaKey) {
                case 'title':
                    $option->setTitles($metaValues);
                    break;
                case 'info_text':
                    $option->setInfotexts($metaValues);
                    break;
                case 'placeholder':
                    $option->setPlaceholders($metaValues);
                    break;
            }
        }
    }

    private function mapMeta(ItemMetadata $item, $meta): void
    {
        $item->setLabels($meta['title']);
        $item->setDescriptions($meta['info_text']);
    }
}
