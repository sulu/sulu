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
     * @var ?array
     */
    protected $parameters;

    /**
     * @var ?string
     */
    protected $filterType;

    /**
     * @var ?array
     */
    protected $filterTypeParameters;

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

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters($parameters): void
    {
        $this->parameters = $parameters;
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
}
