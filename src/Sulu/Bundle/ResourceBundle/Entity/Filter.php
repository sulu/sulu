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

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Filter.
 */
class Filter
{
    /**
     * @var string
     */
    private $conjunction;

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
     * @var string
     */
    private $context;

    /**
     * @var bool
     */
    private $private;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->conditionGroups = new ArrayCollection();
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
     * Add translations.
     *
     * @param FilterTranslation $translations
     *
     * @return Filter
     */
    public function addTranslation(FilterTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param FilterTranslation $translations
     */
    public function removeTranslation(FilterTranslation $translations)
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
     * Add conditionGroups.
     *
     * @param ConditionGroup $conditionGroups
     *
     * @return Filter
     */
    public function addConditionGroup(ConditionGroup $conditionGroups)
    {
        $this->conditionGroups[] = $conditionGroups;

        return $this;
    }

    /**
     * Remove conditionGroups.
     *
     * @param ConditionGroup $conditionGroups
     */
    public function removeConditionGroup(ConditionGroup $conditionGroups)
    {
        $this->conditionGroups->removeElement($conditionGroups);
    }

    /**
     * Get conditionGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getConditionGroups()
    {
        return $this->conditionGroups;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Filter
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return Filter
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return Filter
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return Filter
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set conjunction.
     *
     * @param string $conjunction
     *
     * @return Filter
     */
    public function setConjunction($conjunction)
    {
        $this->conjunction = $conjunction;

        return $this;
    }

    /**
     * Get conjunction.
     *
     * @return string
     */
    public function getConjunction()
    {
        return $this->conjunction;
    }

    /**
     * Set user.
     *
     * @param UserInterface $user
     *
     * @return Filter
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set context.
     *
     * @param string $context
     *
     * @return Filter
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context.
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set private.
     *
     * @param bool $private
     *
     * @return Filter
     */
    public function setPrivate($private)
    {
        $this->private = $private;

        return $this;
    }

    /**
     * Get private.
     *
     * @return bool
     */
    public function getPrivate()
    {
        return $this->private;
    }
}
