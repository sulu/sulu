<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Filter
 */
class Filter
{
    /**
     * @var string
     */
    private $conjunction;

    /**
     * @var string
     */
    private $entityName;

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
     * @var UserInterface
     */
    private $user;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->conditionGroups = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param UserInterface $changer
     * @return Filter
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param UserInterface $creator
     * @return Filter
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set conjunction
     *
     * @param string $conjunction
     * @return Filter
     */
    public function setConjunction($conjunction)
    {
        $this->conjunction = $conjunction;

        return $this;
    }

    /**
     * Get conjunction
     *
     * @return string 
     */
    public function getConjunction()
    {
        return $this->conjunction;
    }

    /**
     * Set user
     *
     * @param UserInterface $user
     * @return Filter
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }
}
