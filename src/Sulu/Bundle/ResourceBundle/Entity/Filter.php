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
    /**
     * @var string
     */
    private $entityName;


    /**
     * Set entityName
     *
     * @param string $entityName
     * @return Filter
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName
     *
     * @return string 
     */
    public function getEntityName()
    {
        return $this->entityName;
    }
    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $changer;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $creator;


    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Filter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Filter
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $changer
     * @return Filter
     */
    public function setChanger(\Sulu\Bundle\SecurityBundle\Entity\User $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User 
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $creator
     * @return Filter
     */
    public function setCreator(\Sulu\Bundle\SecurityBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User 
     */
    public function getCreator()
    {
        return $this->creator;
    }
}
