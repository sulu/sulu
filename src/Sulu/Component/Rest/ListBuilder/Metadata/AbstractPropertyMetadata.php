<?php

declare(strict_types=1);

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

/**
 * Container for property-metadata.
 */
abstract class AbstractPropertyMetadata
{
    private string $name;

    private string $translation;

    private string $visibility = FieldDescriptorInterface::VISIBILITY_NEVER;

    private string $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER;

    private string $type = 'string';

    private bool $sortable = true;

    /** @var array<mixed> */
    private array $transformerTypeParameters;

    private string $filterType;

    /** @var array<mixed> */
    private array $filterTypeParameters;

    private string $width;

    public function __construct(string $name)
    {
        $this->name = $name;
        // default for translation can be overwritten by setter
        $this->translation = \ucfirst($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function setTranslation(string $translation): void
    {
        $this->translation = $translation;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): void
    {
        $this->visibility = $visibility;
    }

    public function getSearchability(): string
    {
        return $this->searchability;
    }

    public function setSearchability(string $searchability): void
    {
        $this->searchability = $searchability;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function setSortable(bool $sortable): void
    {
        $this->sortable = $sortable;
    }

    /** @return array<mixed> */
    public function getTransformerTypeParameters(): array
    {
        return $this->transformerTypeParameters;
    }

    /** @param array<mixed> $transformerTypeParameters */
    public function setTransformerTypeParameters(array $transformerTypeParameters): void
    {
        $this->transformerTypeParameters = $transformerTypeParameters;
    }

    public function getFilterType(): string
    {
        return $this->filterType;
    }

    public function setFilterType(string $filterType): void
    {
        $this->filterType = $filterType;
    }

    /** @return array<mixed> */
    public function getFilterTypeParameters(): array
    {
        return $this->filterTypeParameters;
    }

    /** @param array<mixed> $parameters */
    public function setFilterTypeParameters(array $parameters): void
    {
        $this->filterTypeParameters = $parameters;
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
