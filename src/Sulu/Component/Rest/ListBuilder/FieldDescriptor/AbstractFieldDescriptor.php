<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\ListBuilder\FieldDescriptor;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;

/**
 * This class contains the values required by all FieldDescriptors.
 * @package Sulu\Component\Rest\ListBuilder\FieldDescriptor
 * @ExclusionPolicy("all")
 */
abstract class AbstractFieldDescriptor
{
    /**
     * The name of the field in the database
     * @var string
     * @Expose
     */
    private $name;

    /**
     * The alias name of the field for the response
     * @var string
     * @Expose
     */
    private $alias;

    /**
     * The translation name
     * @var string
     * @Expose
     */
    private $translation;

    /**
     * Defines whether the field is disabled or not
     * @var boolean
     * @Expose
     */
    private $disabled;

    /**
     * The type of the field (only used for special fields like dates)
     * @var string
     * @Expose
     */
    private $type;

    /**
     * The width of the field in a table
     * @var string
     * @Expose
     */
    private $width;

    public function __construct($name, $alias, $disabled = false, $type = '', $width = '', $translation = null)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->disabled = $disabled;
        $this->type = $type;
        $this->width = $width;
        $this->translation = $translation == null ? $name : $translation;
    }

    /**
     * Returns the name of the field
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the alias for the field in the database
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns whether the field is disabled or not
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Returns the translation code of the field
     * @return string
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * Returns the type of the field
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the width of the field
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }
} 
