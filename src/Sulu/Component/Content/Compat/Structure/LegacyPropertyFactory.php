<?php

namespace Sulu\Component\Content\Compat\Structure;

use Sulu\Component\Content\Compat\Property as LegacyProperty;
use Sulu\Component\Content\Compat\Section\SectionProperty;
use Sulu\Component\Content\Compat\Block\BlockProperty;
use Sulu\Component\Content\Structure\Property;
use Sulu\Bundle\DocumentManagerBundle\Bridge\PropertyEncoder;
use Sulu\Component\Content\Compat\Metadata;
use Sulu\Component\Content\Structure\Section;
use Sulu\Component\Content\Structure\Item;
use Sulu\Component\Content\Structure\Block;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\DocumentManager\NamespaceRegistry;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\Compat\PropertyTag;

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
     * Create a new "translated" property
     *
     * @param Item $item
     * @param string $locale
     * @return PropertyInterface
     */
    public function createTranslatedProperty($property, $locale)
    {
        $property = new TranslatedProperty(
            $this->createProperty($property),
            $locale,
            $this->namespaceRegistry->getPrefix('content_localized')
        );

        return $property;
    }

    /**
     * Create a new property
     *
     * @param Item $item
     * @return PropertyInterface $property
     */
    public function createProperty(Item $property)
    {
        if ($property instanceof Section) {
            return $this->createSectionProperty($property);
        }

        if ($property instanceof Block) {
            return $this->createBlockProperty($property);
        }

        if (null === $property->getType()) {
            throw new \RuntimeException(sprintf(
                'Property name "%s" has no type.',
                $property->name
            ));
        }

        $propertyBridge = new LegacyProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
                'placeholder' => $property->placeholder,
            ),
            $property->getType(),
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $property->getParameters(),
            array(),
            $property->getColspan()
        );

        foreach ($property->tags as $tag) {
            $propertyBridge->addTag(new PropertyTag($tag['name'], $tag['priority'], $tag['attributes']));
        }

        return $propertyBridge;
    }

    private function createSectionProperty(Section $property)
    {
        $sectionProperty = new SectionProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
            ),
            $property->getColspan()
        );

        foreach ($property->getChildren() as $child) {
            $sectionProperty->addChild($this->createProperty($child));
        }

        return $sectionProperty;
    }

    private function createBlockProperty(Block $property)
    {
        $blockProperty = new BlockProperty(
            $property->getName(),
            array(
                'title' => $property->title,
                'info_text' => $property->description,
            ),
            'block',
            $property->isRequired(),
            $property->isLocalized(),
            $property->getMaxOccurs(),
            $property->getMinOccurs(),
            $property->getParameters(),
            array(),
            $property->getColspan()
        );

        foreach ($property->getComponents() as $component) {
            $blockPropertyType = new BlockPropertyType(
                $component->getName(),
                array(
                    'title' => $property->title,
                    'info_text' => $property->description,
                )
            );

            foreach ($component->getChildren() as $property) {
                $blockPropertyType->addChild($this->createProperty($property));
            }

            $blockProperty->addType($blockPropertyType);
        }

        return $blockProperty;
    }
}
