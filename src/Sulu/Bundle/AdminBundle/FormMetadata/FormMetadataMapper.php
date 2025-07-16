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
use Sulu\Component\Content\Metadata\BlockMetadata as ContentBlockMetadata;
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
    public function mapChildren(array $children): array
    {
        $items = [];
        foreach ($children as $child) {
            if ($child instanceof ContentBlockMetadata || $child instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($child);
            } elseif ($child instanceof ContentSectionMetadata) {
                $item = $this->mapSection($child);
            } else {
                throw new \Exception('Unsupported property given "' . \get_class($child) . '"');
            }

            $items[$item->getName()] = $item;
        }

        return $items;
    }

    /**
     * @param mixed[] $tagsMetadata
     */
    public function mapTags(array $tagsMetadata): array
    {
        $tags = [];
        foreach ($tagsMetadata as $tagMetadata) {
            $tag = new TagMetadata();
            $tag->setName($tagMetadata['name']);
            $tag->setPriority($tagMetadata['priority'] ?? null);
            $tag->setAttributes($tagMetadata['attributes'] ?? []);

            $tags[] = $tag;
        }

        return $tags;
    }

    private function mapSection(ContentSectionMetadata $property): SectionMetadata
    {
        $section = new SectionMetadata($property->getName());
        $section->setLabels($property->getTitles());
        $section->setDescriptions($property->getDescriptions());
        $section->setColSpan($property->getColSpan());
        $section->setDisabledCondition($property->getDisabledCondition());
        $section->setVisibleCondition($property->getVisibleCondition());

        foreach ($property->getChildren() as $component) {
            if ($component instanceof ContentBlockMetadata || $component instanceof ContentPropertyMetadata) {
                $item = $this->mapProperty($component);
            } elseif ($component instanceof ContentSectionMetadata) {
                $item = $this->mapSection($component);
            } else {
                throw new \Exception('Unsupported property given "' . \get_class($property) . '"');
            }

            $section->addItem($item);
        }

        return $section;
    }

    private function mapProperty(ContentPropertyMetadata $property): FieldMetadata
    {
        $field = new FieldMetadata($property->getName());
        $field->setDefaultType($property->getDefaultComponentName());
        $field->setTags($this->mapTags($property->getTags()));

        $field->setLabels($property->getTitles());
        $field->setDisabledCondition($property->getDisabledCondition());
        $field->setVisibleCondition($property->getVisibleCondition());
        $field->setDescriptions($property->getDescriptions());
        $field->setType($property->getType());
        $field->setColSpan($property->getColSpan());
        $field->setRequired($property->isRequired());
        $field->setMultilingual($property->isLocalized());
        $field->setOnInvalid($property->getOnInvalid());
        $field->setSpaceAfter($property->getSpaceAfter());
        $field->setMinOccurs($property->getMinOccurs());
        $field->setMaxOccurs($property->getMaxOccurs());

        foreach ($property->getParameters() as $parameter) {
            $field->addOption($this->mapOption($parameter));
        }

        foreach ($property->getComponents() as $component) {
            $type = new FormMetadata();
            $type->setKey($component->getName());
            $type->setTitles($component->getTitles());
            $type->setTags($this->mapTags($component->getTags()));

            $typeChildren = $this->mapChildren($component->getChildren());

            foreach ($typeChildren as $typeChild) {
                $type->addItem($typeChild);
            }

            $field->addType($type);
        }

        return $field;
    }

    /**
     * @param array{
     *     name: string,
     *     type: string,
     *     value: bool|int|string|null|OptionMetadata[],
     * } $parameter
     */
    private function mapOption(array $parameter): OptionMetadata
    {
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

        return $option;
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
}
