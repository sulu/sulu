<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\ListMetadata;

class FieldMetadata
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $visibility;

    /**
     * @var bool
     */
    protected $sortable;

    /**
     * @var array|null
     */
    protected $transformerTypeParameters;

    /**
     * @var string|null
     */
    protected $filterType;

    /**
     * @var array|null
     */
    protected $filterTypeParameters;

    /**
     * @var string
     */
    protected $width;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): void
    {
        $this->sortable = $sortable;
    }

    public function getTransformerTypeParameters(): array
    {
        return $this->transformerTypeParameters ?? [];
    }

    public function setTransformerTypeParameters(?array $transformerTypeParameters): void
    {
        $this->transformerTypeParameters = $transformerTypeParameters;
    }

    public function setFilterType(?string $filterType): void
    {
        $this->filterType = $filterType;
    }

    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    public function setFilterTypeParameters(?array $filterTypeParameters): void
    {
        $this->filterTypeParameters = $filterTypeParameters;
    }

    public function getFilterTypeParameters(): ?array
    {
        return $this->filterTypeParameters;
    }

    public function setWidth(string $width): void
    {
        $this->width = $width;
    }

    public function getWidth(): string
    {
        return $this->width;
    }
}
