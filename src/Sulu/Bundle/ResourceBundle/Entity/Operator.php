<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Operator
 */
class Operator
{
    /**
     * @var string
     */
    private $operator;

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
    private $types;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $values;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->types = new \Doctrine\Common\Collections\ArrayCollection();
        $this->values = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set operator
     *
     * @param string $operator
     * @return Operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return string 
     */
    public function getOperator()
    {
        return $this->operator;
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
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations
     * @return Operator
     */
    public function addTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations)
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
     * Add types
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorType $types
     * @return Operator
     */
    public function addType(\Sulu\Bundle\ResourceBundle\Entity\OperatorType $types)
    {
        $this->types[] = $types;

        return $this;
    }

    /**
     * Remove types
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorType $types
     */
    public function removeType(\Sulu\Bundle\ResourceBundle\Entity\OperatorType $types)
    {
        $this->types->removeElement($types);
    }

    /**
     * Get types
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Add values
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values
     * @return Operator
     */
    public function addValue(\Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values)
    {
        $this->values[] = $values;

        return $this;
    }

    /**
     * Remove values
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values
     */
    public function removeValue(\Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values)
    {
        $this->values->removeElement($values);
    }

    /**
     * Get values
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getValues()
    {
        return $this->values;
    }
}
