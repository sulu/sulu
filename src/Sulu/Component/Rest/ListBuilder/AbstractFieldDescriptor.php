<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * This class contains the values required by all FieldDescriptors.
 *
 * @ExclusionPolicy("all")
 */
abstract class AbstractFieldDescriptor
{
    /**
     * The name of the field in the database.
     *
     * @var string
     * @Expose
     */
    private $name;

    /**
     * The translation name.
     *
     * @var string
     * @Expose
     */
    private $translation;

    /**
     * Defines whether the field is disabled or not.
     *
     * @var bool
     * @Expose
     */
    private $disabled;

    /**
     * Defines whether the field is hideable or not.
     *
     * @var bool
     * @Expose
     */
    private $default;

    /**
     * Defines if this field is sortable.
     *
     * @var bool
     * @Expose
     */
    private $sortable;

    /**
     * The type of the field (only used for special fields like dates).
     *
     * @var string
     * @Expose
     */
    private $type;

    /**
     * The width of the field in a table.
     *
     * @var string
     * @Expose
     */
    private $width;

    /**
     * The minimal with of the field in the table.
     *
     * @var string
     * @Expose
     */
    private $minWidth;

    /**
     * Defines whether the field is editable in the table or not.
     *
     * @var bool
     * @Expose
     */
    private $editable;

    /**
     * The css class of the column.
     *
     * @var string
     * @Expose
     */
    private $class;

    public function __construct(
        $name,
        $translation = null,
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $sortable = true,
        $editable = false,
        $cssClass = ''
    ) {
        $this->name = $name;
        $this->disabled = $disabled;
        $this->default = $default;
        $this->sortable = $sortable;
        $this->type = $type;
        $this->width = $width;
        $this->minWidth = $minWidth;
        $this->editable = $editable;
        $this->translation = $translation == null ? $name : $translation;
        $this->class = $cssClass;
    }

    /**
     * Returns the name of the field.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns whether the field is disabled or not.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Returns the translation code of the field.
     *
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * Returns the type of the field.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the width of the field.
     *
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return bool
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function getSortable()
    {
        return $this->sortable;
    }

    /**
     * @return bool
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * @return string
     */
    public function getMinWidth()
    {
        return $this->minWidth;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
