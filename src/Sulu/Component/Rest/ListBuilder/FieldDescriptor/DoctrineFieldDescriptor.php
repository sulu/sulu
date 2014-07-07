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

/**
 * This class defines the necessary information for a field to resolve it within a Doctrine Query for the ListBuilder.
 * @package Sulu\Component\Rest\ListBuilder\FieldDescriptor
 */
class DoctrineFieldDescriptor
{
    /**
     * The name of the field in the database
     * @var string
     */
    private $name;

    /**
     * The alias name of the field for the response
     * @var string
     */
    private $alias;

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

    public function __construct($name, $alias, $entityName, $joins = array())
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->entityName = $entityName;
        $this->joins = $joins;
    }

    public function getFullName()
    {
        return $this->entityName . '.' . $this->name;
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
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }
}
