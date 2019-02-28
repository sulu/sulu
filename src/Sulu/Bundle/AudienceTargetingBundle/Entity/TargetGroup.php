<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Entity class for target group.
 */
class TargetGroup implements TargetGroupInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $priority;

    /**
     * @var bool
     */
    private $allWebspaces = false;

    /**
     * @var bool
     */
    private $active = false;

    /**
     * @var TargetGroupWebspaceInterface[]
     */
    private $webspaces;

    /**
     * @var TargetGroupRuleInterface[]
     */
    private $rules;

    /**
     * Initialization of collections.
     */
    public function __construct()
    {
        $this->webspaces = new ArrayCollection();
        $this->rules = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllWebspaces()
    {
        return $this->allWebspaces;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllWebspaces($allWebspaces)
    {
        $this->allWebspaces = $allWebspaces;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * {@inheritdoc}
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaces()
    {
        return $this->webspaces;
    }

    /**
     * {@inheritdoc}
     */
    public function addWebspace(TargetGroupWebspaceInterface $webspace)
    {
        $this->webspaces[] = $webspace;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeWebspace(TargetGroupWebspaceInterface $webspace)
    {
        $this->webspaces->removeElement($webspace);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearWebspaces()
    {
        $this->webspaces->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function addRule(TargetGroupRuleInterface $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeRule(TargetGroupRuleInterface $rule)
    {
        $this->rules->removeElement($rule);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clearRules()
    {
        $this->rules->clear();
    }
}
