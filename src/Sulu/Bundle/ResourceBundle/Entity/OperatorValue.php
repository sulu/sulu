<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperatorValue
 */
class OperatorValue
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\Operator
     */
    private $operator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation $translations
     * @return OperatorValue
     */
    public function addTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorValueTranslation $translations)
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
     * Set operator
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Operator $operator
     * @return OperatorValue
     */
    public function setOperator(\Sulu\Bundle\ResourceBundle\Entity\Operator $operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\Operator 
     */
    public function getOperator()
    {
        return $this->operator;
    }
    /**
     * @var int
     */
    private $type;


    /**
     * Set type
     *
     * @param \int $type
     * @return OperatorValue
     */
    public function setType(\int $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \int 
     */
    public function getType()
    {
        return $this->type;
    }
}
