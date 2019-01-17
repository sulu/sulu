<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var string
     */
    private $width = '';

    /**
     * @var string
     */
    private $minWidth = '';

    /**
     * @var bool
     */
    private $sortable = true;

    /**
     * @var bool
     */
    private $editable = false;

    /**
     * @var string
     */
    private $cssClass = '';

    /**
     * @var string
     */
    private $filterType;

    /**
     * @var array
     */
    private $filterTypeParameters = [];

    public function __construct($name)
    {
        $this->name = $name;
        // default for translation can be overwritten by setter
        $this->translation = ucfirst($name);
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
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return string
     */
    public function getMinWidth()
    {
        return $this->minWidth;
    }

    /**
     * @param string $minWidth
     */
    public function setMinWidth($minWidth)
    {
        $this->minWidth = $minWidth;
    }

    /**
     * @return bool
     * @return bool
     */
    public function isSortable()
    {
        return $this->sortable;
    }

    /**
     * @param bool $sortable
     * @param bool $sortable
     */
    public function setSortable($sortable)
    {
        $this->sortable = $sortable;
    }

    /**
     * @return bool
     * @return bool
     */
    public function isEditable()
    {
        return $this->editable;
    }

    /**
     * @param bool $editable
     * @param bool $editable
     */
    public function setEditable($editable)
    {
        $this->editable = $editable;
    }

    /**
     * @return string
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * @param string $cssClass
     */
    public function setCssClass($cssClass)
    {
        $this->cssClass = $cssClass;
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
     */
    public function setFilterType($filterType)
    {
        $this->filterType = $filterType;
    }

    /**
     * @return array
     */
    public function getFilterTypeParameters()
    {
        return $this->filterTypeParameters;
    }

    /**
     * @param array $parameters
     */
    public function setFilterTypeParameters($parameters)
    {
        $this->filterTypeParameters = $parameters;
    }
}
