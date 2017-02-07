<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Compat\Section\SectionProperty;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ItemMetadata;
use Sulu\Component\Content\Metadata\Property;
use Sulu\Component\Content\Metadata\SectionMetadata;
use Sulu\Component\DocumentManager\NamespaceRegistry;

/**
 * Creates legacy properties from "new" properties.
 *
 * @deprecated
 */
class LegacyPropertyFactory
{
    private $namespaceRegistry;

    /**
     * @param NamespaceRegistry $namespaceRegistry
     */
    public function __construct(NamespaceRegistry $namespaceRegistry)
    {
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * Create a new "translated" property.
     *
     * @param object $property
     * @param string $locale
     *
     * @return PropertyInterface
     */
    public function createTranslatedProperty($property, $locale, StructureInterface $structure = null)
    {
        if ($property instanceof ItemMetadata) {
            $property = $this->createProperty($property, $structure);
        }
        $property = new TranslatedProperty(
            $property,
            $locale,
            $this->namespaceRegistry->getPrefix('content_localized')
        );

        return $property;
    }

    /**
     * Create a new property.
     *
     * @param Item $item
     *
     * @return PropertyInterface $property
     */
    public function createProperty(ItemMetadata $property, StructureInterface $structure = null)
    {
        if ($property instanceof SectionMetadata) {
            return $this->createSectionProperty($property, $structure);
        }

        if ($property instanceof BlockMetadata) {
            return $this->createBlockProperty($property, $structure);
        }

        if (null === $property->getType()) {
            throw new \RuntimeException(sprintf(
                'Property name "%s" has no type.',
                $property->name
            ));
        }

        $parameters = $this->convertArrayToParameters($property->getParameters());
        $propertyBridge = new LegacyProperty(
            $property->getName(),
            [
                'title' => $property->title,
                'info_text' => $property->description,
                'placeholder' => $property->placeholder,
            ],
            $property->getType(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $parameters,
            [],
            $property->getColspan()
        );

        foreach ($property->tags as $tag) {
            $propertyBridge->addTag(new PropertyTag($tag['name'], $tag['priority'], $tag['attributes']));
        }

        $propertyBridge->setStructure($structure);

        return $propertyBridge;
    }

    private function convertArrayToParameters($arrayParams)
    {
        $parameters = [];
        foreach ($arrayParams as $arrayParam) {
            $value = $arrayParam['value'];

            if (is_array($value)) {
                $value = $this->convertArrayToParameters($value);
            }

            $parameters[$arrayParam['name']] = new PropertyParameter($arrayParam['name'], $value, $arrayParam['type'], $arrayParam['meta']);
        }

        return $parameters;
    }

    private function createSectionProperty(SectionMetadata $property, StructureInterface $structure = null)
    {
        $sectionProperty = new SectionProperty(
            $property->getName(),
            [
                'title' => $property->title,
                'info_text' => $property->description,
            ],
            $property->getColspan()
        );

        foreach ($property->getChildren() as $child) {
            $sectionProperty->addChild($this->createProperty($child, $structure));
        }

        return $sectionProperty;
    }

    private function createBlockProperty(BlockMetadata $property, StructureInterface $structure = null)
    {
        $blockProperty = new BlockProperty(
            $property->getName(),
            [
                'title' => $property->title,
                'info_text' => $property->description,
            ],
            $property->getDefaultComponentName(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $property->getParameters(),
            [],
            $property->getColspan()
        );
        $blockProperty->setStructure($structure);

        foreach ($property->getComponents() as $component) {
            $blockPropertyType = new BlockPropertyType(
                $component->getName(),
                [
                    'title' => $component->title,
                    'info_text' => $component->description,
                ]
            );

            foreach ($component->getChildren() as $property) {
                $blockPropertyType->addChild($this->createProperty($property, $structure));
            }

            $blockProperty->addType($blockPropertyType);
        }

        return $blockProperty;
    }
}
