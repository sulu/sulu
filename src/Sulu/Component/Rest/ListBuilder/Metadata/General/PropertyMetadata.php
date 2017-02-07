<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\Metadata\General;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * Container for property-metadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    const DISPLAY_ALWAYS = 'always';
    const DISPLAY_NEVER = 'never';
    const DISPLAY_YES = 'yes';
    const DISPLAY_NO = 'no';

    /**
     * @var string
     */
    private $translation;

    /**
     * @var string
     */
    private $display = self::DISPLAY_NO;

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

    public function __construct($class, $name)
    {
        $this->class = $class;
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
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param string $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
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
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getMinWidth()
    {
        return $this->minWidth;
    }

    /**
     * @param int $minWidth
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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->class,
                $this->name,
                $this->translation,
                $this->display,
                $this->type,
                $this->width,
                $this->minWidth,
                $this->sortable,
                $this->editable,
                $this->cssClass,
                $this->filterType,
                $this->filterTypeParameters,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->translation,
            $this->display,
            $this->type,
            $this->width,
            $this->minWidth,
            $this->sortable,
            $this->editable,
            $this->cssClass,
            $this->filterType,
            $this->filterTypeParameters) = unserialize($str);
    }
}
