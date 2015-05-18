<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Api;

use Sulu\Bundle\ResourceBundle\Resource\DataTypes;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ResourceBundle\Entity\Condition as ConditionEntity;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * The Condition class which will be exported to the API
 *
 * @package Sulu\Bundle\ResourceBundle\Api
 * @ExclusionPolicy("all")
 */
class Condition extends ApiWrapper
{
    /**
     * @param ConditionEntity $entity
     * @param string $locale
     */
    public function __construct(ConditionEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Get id
     *
     * @VirtualProperty
     * @SerializedName("id")
     * @return integer
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get value
     *
     * @VirtualProperty
     * @SerializedName("value")
     * @return mixed
     */
    public function getValue()
    {
        // return a proper date if it is a datetime value
        if($this->getType() === DataTypes::DATETIME_TYPE) {
            return new \DateTime($this->entity->getValue());
        }
        return $this->entity->getValue();
    }

    /**
     * Get field
     *
     * @VirtualProperty
     * @SerializedName("field")
     * @return string
     */
    public function getField()
    {
        return $this->entity->getField();
    }

    /**
     * Get operator
     *
     * @VirtualProperty
     * @SerializedName("operator")
     * @return string
     */
    public function getOperator()
    {
        return $this->entity->getOperator();
    }

    /**
     * Get type
     *
     * @VirtualProperty
     * @SerializedName("type")
     * @return integer
     */
    public function getType()
    {
        return $this->entity->getType();
    }

    /**
     * Set field
     *
     * @param string $field
     */
    public function setField($field)
    {
        $this->entity->setField($field);
    }

    /**
     * Set operator
     *
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->entity->setOperator($operator);
    }

    /**
     * Set type
     *
     * @param integer $type
     */
    public function setType($type)
    {
        $this->entity->setType($type);
    }

    /**
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->entity->setValue($value);
    }

    /**
     * Set conditionGroup
     *
     * @param ConditionGroup $conditionGroup
     */
    public function setConditionGroup(ConditionGroup $conditionGroup)
    {
        $this->entity->setConditionGroup($conditionGroup->getEntity());
    }

    /**
     * Get conditionGroup
     *
     * @return ConditionGroup
     */
    public function getConditionGroup()
    {
        return new ConditionGroup($this->entity->getConditionGroup(), $this->locale);
    }
}
