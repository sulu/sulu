<?php

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
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $translation;

    /**
     * @var string
     */
    private $visibility = FieldDescriptorInterface::VISIBILITY_NEVER;

    /**
     * @var string
     */
    private $searchability = FieldDescriptorInterface::SEARCHABILITY_NEVER;

    /**
     * @var string
     */
    private $type = 'string';

    /**
     * @var bool
     */
    private $sortable = true;

    /**
     * @var array<mixed>|null
     */
    private $transformerTypeParameters = null;

    /**
     * @var string
     */
    private $filterType;

    /**
     * @var array<mixed>|null
     */
    private $filterTypeParameters = null;

    /**
     * @var string
     */
    private $width;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        // default for translation can be overwritten by setter
        $this->translation = \ucfirst($name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * @param string $translation
     *
     * @return void
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     *
     * @return void
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * @return string
     */
    public function getSearchability()
    {
        return $this->searchability;
    }

    /**
     * @param string $searchability
     *
     * @return void
     */
    public function setSearchability($searchability)
    {
        $this->searchability = $searchability;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param bool $sortable
     *
     * @return void
     */
    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
    }

    /**
     * @return array<mixed>|null
     */
    public function getTransformerTypeParameters()
    {
        return $this->transformerTypeParameters;
    }

    /**
     * @param array<mixed> $transformerTypeParameters
     *
     * @return void
     */
    public function setTransformerTypeParameters($transformerTypeParameters)
    {
        $this->transformerTypeParameters = $transformerTypeParameters;
    }

    /**
     * @return string
     */
    public function getFilterType()
    {
        return $this->filterType;
    }

    /**
     * @param string $filterType
     *
     * @return void
     */
    public function setFilterType($filterType)
    {
        $this->filterType = $filterType;
    }

    /**
     * @return array<mixed>|null
     */
    public function getFilterTypeParameters()
    {
        return $this->filterTypeParameters;
    }

    /**
     * @param array<mixed> $parameters
     *
     * @return void
     */
    public function setFilterTypeParameters($parameters)
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
