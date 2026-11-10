<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

class FieldMetadata extends ItemMetadata
{
    /**
     * @var OptionMetadata[]
     */
    protected $options = [];

    /**
     * @var FormMetadata[]
     */
    protected $types = [];

    /**
     * @var string|null
     */
    protected $defaultType;

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var bool
     */
    protected $multilingual;

    /**
     * @var null|int
     */
    protected $spaceAfter;

    /**
     * @var null|int
     */
    protected $minOccurs;

    /**
     * @var null|int
     */
    protected $maxOccurs;

    /**
     * @var string
     */
    protected $onInvalid;

    /**
     * @var TagMetadata[]
     */
    protected $tags = [];

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<OptionMetadata>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function addOption(OptionMetadata $option): void
    {
        $name = $option->getName();
        $this->options[\is_int($name) ? $name : ((string) $name)] = $option;
    }

    public function findOption(string $name): ?OptionMetadata
    {
        return $this->options[$name] ?? null;
    }

    public function getDefaultType(): ?string
    {
        return $this->defaultType;
    }

    public function setDefaultType(?string $defaultType): void
    {
        $this->defaultType = $defaultType;
    }

    /**
     * @return array<FormMetadata>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * @param array<FormMetadata> $types
     */
    public function setTypes(array $types): void
    {
        $this->types = [];
        foreach ($types as $type) {
            $this->addType($type);
        }
    }

    public function addType(FormMetadata $type): void
    {
        $this->types[$type->getKey()] = $type;
    }

    public function removeType(string $type): void
    {
        unset($this->types[$type]);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function isMultilingual(): bool
    {
        return $this->multilingual;
    }

    public function setMultilingual(bool $multilingual): void
    {
        $this->multilingual = $multilingual;
    }

    public function getSpaceAfter(): ?int
    {
        return $this->spaceAfter;
    }

    public function setSpaceAfter(?int $spaceAfter = null): void
    {
        $this->spaceAfter = $spaceAfter;
    }

    public function setMinOccurs(?int $minOccurs = null): void
    {
        $this->minOccurs = $minOccurs;
    }

    public function getMinOccurs(): ?int
    {
        return $this->minOccurs;
    }

    public function setMaxOccurs(?int $maxOccurs = null): void
    {
        $this->maxOccurs = $maxOccurs;
    }

    public function getMaxOccurs(): ?int
    {
        return $this->maxOccurs;
    }

    public function setOnInvalid(?string $onInvalid = null): void
    {
        $this->onInvalid = $onInvalid;
    }

    public function getOnInvalid(): ?string
    {
        return $this->onInvalid;
    }

    /**
     * @return array<TagMetadata>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function hasTag(string $name): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    public function addTag(TagMetadata $tag): void
    {
        $this->tags[] = $tag;
    }

    /**
     * @param TagMetadata[] $tags
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    public function merge(FieldMetadata $otherMetaData): FieldMetadata
    {
        $mergedMetaData = new self($this->name);
        $mergedMetaData->setType($this->type);
        $mergedMetaData->setColSpan($this->colSpan);
        $mergedMetaData->setDefaultType($this->defaultType);
        $mergedMetaData->setMaxOccurs($this->maxOccurs);
        $mergedMetaData->setMinOccurs($this->minOccurs);
        $mergedMetaData->setOnInvalid($this->onInvalid);
        $mergedMetaData->setRequired($this->required);
        $mergedMetaData->setDisabledCondition($this->disabledCondition);
        $mergedMetaData->setVisibleCondition($this->visibleCondition);
        $mergedMetaData->setSpaceAfter($this->spaceAfter);
        $mergedMetaData->setMultilingual($this->multilingual);

        $mergedMetaData->setDescriptions(\array_merge($this->descriptions, $otherMetaData->getDescriptions()));
        $mergedMetaData->setLabels(\array_merge($this->labels, $otherMetaData->getLabels()));
        $mergedMetaData->setTags(\array_merge($this->tags, $otherMetaData->getTags()));
        $mergedMetaData->setTypes(\array_merge($this->types, $otherMetaData->getTypes()));

        return $mergedMetaData;
    }
}
