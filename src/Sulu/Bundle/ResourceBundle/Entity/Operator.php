<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

/**
 * Operator.
 */
class Operator
{
    /**
     * @var string
     */
    private $operator;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $inputType;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $values;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->values = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set operator.
     *
     * @param string $operator
     *
     * @return Operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Get operator.
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return Operator
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set inputType.
     *
     * @param string $inputType
     *
     * @return Operator
     */
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;

        return $this;
    }

    /**
     * Get inputType.
     *
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param $id integer
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Add translations.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations
     *
     * @return Operator
     */
    public function addTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\ResourceBundle\Entity\OperatorTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add values.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values
     *
     * @return Operator
     */
    public function addValue(\Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values)
    {
        $this->values[] = $values;

        return $this;
    }

    /**
     * Remove values.
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values
     */
    public function removeValue(\Sulu\Bundle\ResourceBundle\Entity\OperatorValue $values)
    {
        $this->values->removeElement($values);
    }

    /**
     * Get values.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getValues()
    {
        return $this->values;
    }
}
