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

use Sulu\Bundle\ResourceBundle\Entity\FilterTranslation;
use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ResourceBundle\Entity\Filter as FilterEntity;
use Sulu\Bundle\ResourceBundle\Api\ConditionGroup;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Hateoas\Configuration\Annotation\Relation;

/**
 * The Filter class which will be exported to the API
 *
 * @package Sulu\Bundle\ResourceBundle\Api
 * @Relation("self", href="expr('/admin/api/filters/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class Filter extends ApiWrapper
{

    /**
     * @param FilterEntity $entity
     * @param string $locale
     */
    public function __construct(FilterEntity $entity, $locale)
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
     * Returns the name of the filter
     *
     * @return string The name of the filter
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName()
    {
        return $this->getTranslation()->getName();
    }

    /**
     * Get andCombination
     *
     * @VirtualProperty
     * @SerializedName("andCombination")
     * @return boolean
     */
    public function getAndCombination()
    {
        return $this->entity->getAndCombination();
    }

    /**
     * Get entity
     *
     * @VirtualProperty
     * @SerializedName("entityName")
     * @return string
     */
    public function getEntityName()
    {
        return $this->entity->getEntityName();
    }

    /**
     * Get conditionGroups
     *
     * @VirtualProperty
     * @SerializedName("conditionGroups")
     * @return null|ConditionGroup[]
     */
    public function getConditionGroups()
    {
        $groups = $this->entity->getConditionGroups();
        if ($groups) {
            $result = [];
            foreach ($groups as $group) {
                $result[] = new ConditionGroup($group, $this->locale);
            }

            return $result;
        }

        return null;
    }

    /**
     * Set andCombination
     *
     * @param boolean $andCombination
     * @return Filter
     */
    public function setAndCombination($andCombination)
    {
        return $this->entity->setAndCombination($andCombination);
    }

    /**
     * Set entity
     *
     * @param $name
     * @return Filter
     */
    public function setEntityName($name)
    {
        return $this->entity->setEntityName($name);
    }

    /**
     * Add translations
     *
     * @param FilterTranslation $translation
     * @return Filter
     */
    public function addTranslation(FilterTranslation $translation)
    {
        return $this->entity->addTranslation($translation);
    }

    /**
     * Remove translations
     *
     * @param FilterTranslation $translations
     * @return bool
     */
    public function removeTranslation(FilterTranslation $translations)
    {
        return $this->entity->getTranslations()->removeElement($translations);
    }

    /**
     * Get translation by locale
     *
     * @return FilterTranslation
     */
    private function getTranslation()
    {
        $filterTranslation = null;
        foreach ($this->entity->getTranslations() as $translation) {
            if ($translation->getLocale() == $this->locale) {
                $filterTranslation = $translation;
            }
        }
        if (!$filterTranslation) {
            $filterTranslation = new FilterTranslation();
            $filterTranslation->setLocale($this->locale);
            $filterTranslation->setFilter($this->entity);
            $this->entity->addTranslation($filterTranslation);
        }

        return $filterTranslation;
    }

    /**
     * Sets the name of the product
     * @param string $name The name of the product
     */
    public function setName($name)
    {
        $this->getTranslation()->setName($name);
    }

    /**
     * Add conditionGroups
     *
     * @param ConditionGroup $conditionGroup
     * @return Filter
     * @internal param ConditionGroup $conditionGroups
     */
    public function addConditionGroup(ConditionGroup $conditionGroup)
    {
        return $this->entity->addConditionGroup($conditionGroup->getEntity());
    }

    /**
     * Remove conditionGroup
     *
     * @param ConditionGroup $conditionGroup
     */
    public function removeConditionGroup(ConditionGroup $conditionGroup)
    {
        $this->entity->removeConditionGroup($conditionGroup->getEntity());
    }

}
