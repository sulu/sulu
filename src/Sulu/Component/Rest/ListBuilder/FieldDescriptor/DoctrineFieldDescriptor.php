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

/**
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 * @package Sulu\Component\Rest\ListBuilder\FieldDescriptor
 * @ExclusionPolicy("all")
 */
class DoctrineFieldDescriptor extends AbstractFieldDescriptor
{
    /**
     * The name of the field in the database
     * @var string
     */
    private $fieldName;

    /**
     * The name of the entity
     * @var string
     */
    private $entityName;

    /**
     * The joins, which have to be made to get to the result
     * @var array
     */
    private $joins;

    public function __construct(
        $fieldName,
        $name,
        $entityName,
        $joins = array(),
        $disabled = false,
        $default = false,
        $type = '',
        $width = '',
        $minWidth = '',
        $translation = null
    )
    {
        parent::__construct($name, $disabled, $default, $type, $width, $minWidth, $translation);

        $this->fieldName = $fieldName;
        $this->entityName = $entityName;
        $this->joins = $joins;
    }

    /**
     * Returns the full name of the field, including the entity
     * @return string
     */
    public function getFullName()
    {
        return $this->entityName . '.' . $this->getFieldName();
    }

    /**
     * Returns the alias for the field in the database
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Returns the name of the entity this field is contained in
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Returns all the joins which are necessary to access this field
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }
}
