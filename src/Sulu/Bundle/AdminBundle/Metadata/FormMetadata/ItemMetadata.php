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

use JMS\Serializer\Annotation as Serializer;

abstract class ItemMetadata
{
    /**
     * @var string
     */
    #[Serializer\Exclude]
    protected $name;

    /**
     * @var array<string, string>
     */
    #[Serializer\Exclude]
    protected $labels = [];

    /**
     * @var array<string, string>
     */
    #[Serializer\Exclude]
    protected $descriptions = [];

    /**
     * @var string
     */
    protected $disabledCondition;

    /**
     * @var string
     */
    protected $visibleCondition;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $colSpan = 12;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLabel(string $label, string $locale)
    {
        $this->labels[$locale] = $label;
    }

    /**
     * @return array<string, string>
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getLabel(string $locale): ?string
    {
        if (isset($this->labels[$locale])) {
            return $this->labels[$locale];
        }

        return \count($this->labels) ? $this->labels[\array_key_first($this->labels)] : null;
    }

    /**
     * @param array<string, string> $labels
     */
    public function setLabels(array $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @return array<string, string>
     */
    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    public function getDescription(string $locale): ?string
    {
        if (isset($this->descriptions[$locale])) {
            return $this->descriptions[$locale];
        }

        return \count($this->descriptions) ? $this->descriptions[\array_key_first($this->descriptions)] : null;
    }

    /**
     * @param array<string, string> $descriptions
     */
    public function setDescriptions(array $descriptions): void
    {
        $this->descriptions = $descriptions;
    }

    public function setDescription(string $description, string $locale)
    {
        $this->descriptions[$locale] = $description;
    }

    public function getDisabledCondition(): ?string
    {
        return $this->disabledCondition;
    }

    public function setDisabledCondition(?string $disabledCondition): void
    {
        $this->disabledCondition = $disabledCondition;
    }

    public function getVisibleCondition(): ?string
    {
        return $this->visibleCondition;
    }

    public function setVisibleCondition(?string $visibleCondition): void
    {
        $this->visibleCondition = $visibleCondition;
    }

    public function getColSpan(): int
    {
        return $this->colSpan;
    }

    public function setColSpan(int $colSpan): void
    {
        $this->colSpan = $colSpan;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
