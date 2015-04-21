<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Filter
 */
class Filter
{
    /**
     * @var boolean
     */
    private $andCombination;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $conditionGroups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->conditionGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set andCombination
     *
     * @param boolean $andCombination
     * @return Filter
     */
    public function setAndCombination($andCombination)
    {
        $this->andCombination = $andCombination;

        return $this;
    }

    /**
     * Get andCombination
     *
     * @return boolean 
     */
    public function getAndCombination()
    {
        return $this->andCombination;
    }

    /**
     * Set entity
     *
     * @param string $entity
     * @return Filter
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity
     *
     * @return string 
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add translations
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\FilterTranslation $translations
     * @return Filter
     */
    public function addTranslation(\Sulu\Bundle\ResourceBundle\Entity\FilterTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\FilterTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\ResourceBundle\Entity\FilterTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add conditionGroups
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroups
     * @return Filter
     */
    public function addConditionGroup(\Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroups)
    {
        $this->conditionGroups[] = $conditionGroups;

        return $this;
    }

    /**
     * Remove conditionGroups
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroups
     */
    public function removeConditionGroup(\Sulu\Bundle\ResourceBundle\Entity\ConditionGroup $conditionGroups)
    {
        $this->conditionGroups->removeElement($conditionGroups);
    }

    /**
     * Get conditionGroups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getConditionGroups()
    {
        return $this->conditionGroups;
    }
}
