<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TagMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Component\Content\Metadata\BlockMetadata;
use Sulu\Component\Content\Metadata\ItemMetadata as ContentItemMetadata;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\Metadata\SectionMetadata as ContentSectionMetadata;

/**
 * Maps the deprecated form metadata objects to the actual ones.
 */
class FormMetadataMapper
{
    /**
     * @return ItemMetadata[]
     */
    public function mapChildren(array $children, string $locale): array
    {
        $items = [];
        foreach ($children as $child) {
            if ($child instanceof BlockMetadata) {
                $item = $this->mapBlock($child, $locale);
            } elseif ($child instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($child, $locale);
            } elseif ($child instanceof ContentSectionMetadata) {
                $item = $this->mapSection($child, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($child) . '"');
            }

            $items[$item->getName()] = $item;
        }

        return $items;
    }

    /**
     * @param ContentItemMetadata[] $itemsMetadata
     */
    public function mapSchema(array $itemsMetadata): SchemaMetadata
    {
        return new SchemaMetadata($this->mapSchemaProperties($itemsMetadata));
    }

    private function mapSection(ContentSectionMetadata $property, string $locale): SectionMetadata
    {
        $section = new SectionMetadata($property->getName());

        $title = $property->getTitle($locale);
        if ($title) {
            $section->setLabel($title);
        }

        $section->setColSpan($property->getColSpan());
        $section->setDisabledCondition($property->getDisabledCondition());
        $section->setVisibleCondition($property->getVisibleCondition());

        foreach ($property->getChildren() as $component) {
            if ($component instanceof BlockMetadata) {
                $item = $this->mapBlock($component, $locale);
            } elseif ($component instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($component, $locale);
            } elseif ($component instanceof ContentSectionMetadata) {
                $item = $this->mapSection($component, $locale);
            } else {
                throw new \Exception('Unsupported property given "' . get_class($property) . '"');
            }

            $section->addItem($item);
        }

        return $section;
    }

    private function mapBlock(BlockMetadata $property, string $locale): FieldMetadata
    {
        $field = $this->mapProperty($property, $locale);
        $field->setDefaultType($property->getDefaultComponentName());

        foreach ($property->getComponents() as $component) {
            $blockType = new FormMetadata();
            $blockType->setName($component->getName());
            $blockType->setTitle($component->getTitle($locale) ?? ucfirst($component->getName()));

            foreach ($component->getChildren() as $componentProperty) {
                if ($componentProperty instanceof ContentPropertyMetadata) {
                    $blockTypeField = $this->mapProperty($componentProperty, $locale);
                    $blockType->addItem($blockTypeField);
                }
            }

            $field->addType($blockType);
        }

        return $field;
    }

    private function mapProperty(ContentPropertyMetadata $property, string $locale): FieldMetadata
    {
        $field = new FieldMetadata($property->getName());
        foreach ($property->getTags() as $tag) {
            $fieldTag = new TagMetadata();
            $fieldTag->setName($tag['name']);
            $fieldTag->setPriority($tag['priority']);
            $field->addTag($fieldTag);
        }

        $field->setLabel($property->getTitle($locale));
        $field->setDisabledCondition($property->getDisabledCondition());
        $field->setVisibleCondition($property->getVisibleCondition());
        $field->setDescription($property->getDescription($locale));
        $field->setType($property->getType());
        $field->setColSpan($property->getColSpan());
        $field->setRequired($property->isRequired());
        $field->setOnInvalid($property->getOnInvalid());
        $field->setSpaceAfter($property->getSpaceAfter());

        foreach ($property->getParameters() as $parameter) {
            $field->addOption($this->mapOption($parameter, $locale));
        }

        return $field;
    }

    private function mapOption(array $parameter, string $locale): OptionMetadata
    {
        $option = new OptionMetadata();
        $option->setName($parameter['name']);
        $option->setType($parameter['type']);

        if ('collection' === $parameter['type']) {
            foreach ($parameter['value'] as $parameterName => $parameterValue) {
                $valueOption = new OptionMetadata();
                $valueOption->setName($parameterValue['name']);
                $valueOption->setValue($parameterValue['value']);

                $this->mapOptionMeta($parameterValue, $locale, $valueOption);

                $option->addValueOption($valueOption);
            }
        } elseif ('string' === $parameter['type'] || 'expression' === $parameter['type']) {
            $option->setValue($parameter['value']);
            $this->mapOptionMeta($parameter, $locale, $option);
        } else {
            throw new \Exception('Unsupported parameter given "' . get_class($parameter) . '"');
        }

        return $option;
    }

    private function mapOptionMeta(array $parameterValue, string $locale, OptionMetadata $option): void
    {
        if (!array_key_exists('meta', $parameterValue)) {
            return;
        }

        foreach ($parameterValue['meta'] as $metaKey => $metaValues) {
            if (array_key_exists($locale, $metaValues)) {
                switch ($metaKey) {
                    case 'title':
                        $option->setTitle($metaValues[$locale]);
                        break;
                    case 'info_text':
                        $option->setInfotext($metaValues[$locale]);
                        break;
                    case 'placeholder':
                        $option->setPlaceholder($metaValues[$locale]);
                        break;
                }
            }
        }
    }

    /**
     * @param ContentItemMetadata[] $itemsMetadata
     *
     * @return ItemMetadata[]
     */
    private function mapSchemaProperties(array $itemsMetadata): array
    {
        return array_filter(array_map(function(ContentItemMetadata $itemMetadata) {
            if ($itemMetadata instanceof ContentSectionMetadata) {
                return $this->mapSchemaProperties($itemMetadata->getChildren());
            }

            if (!$itemMetadata->isRequired()) {
                return;
            }

            return new PropertyMetadata($itemMetadata->getName(), $itemMetadata->isRequired());
        }, $itemsMetadata));
    }
}
