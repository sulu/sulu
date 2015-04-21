<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperatorType
 */
class OperatorType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $inputType;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\Operator
     */
    private $operator;


    /**
     * Set name
     *
     * @param string $name
     * @return OperatorType
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
     * Set inputType
     *
     * @param string $inputType
     * @return OperatorType
     */
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;

        return $this;
    }

    /**
     * Get inputType
     *
     * @return string 
     */
    public function getInputType()
    {
        return $this->inputType;
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
     * Set operator
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Operator $operator
     * @return OperatorType
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
}
