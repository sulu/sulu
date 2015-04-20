<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Filter
 */
class Filter
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $andCombination;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $conditions;

    /**
     * @var integer
     */
    private $id;


    /**
     * Set name
     *
     * @param string $name
     * @return Filter
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
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
     * Set conditions
     *
     * @param string $conditions
     * @return Filter
     */
    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * Get conditions
     *
     * @return string 
     */
    public function getConditions()
    {
        return $this->conditions;
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
}
