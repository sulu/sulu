<?php

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConditionGroup
 */
class ConditionGroup
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $conditions;

    /**
     * @var \Sulu\Bundle\ResourceBundle\Entity\Filter
     */
    private $filter;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->conditions = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add conditions
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Condition $conditions
     * @return ConditionGroup
     */
    public function addCondition(\Sulu\Bundle\ResourceBundle\Entity\Condition $conditions)
    {
        $this->conditions[] = $conditions;

        return $this;
    }

    /**
     * Remove conditions
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Condition $conditions
     */
    public function removeCondition(\Sulu\Bundle\ResourceBundle\Entity\Condition $conditions)
    {
        $this->conditions->removeElement($conditions);
    }

    /**
     * Get conditions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set filter
     *
     * @param \Sulu\Bundle\ResourceBundle\Entity\Filter $filter
     * @return ConditionGroup
     */
    public function setFilter(\Sulu\Bundle\ResourceBundle\Entity\Filter $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Get filter
     *
     * @return \Sulu\Bundle\ResourceBundle\Entity\Filter 
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
