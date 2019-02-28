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

/**
 * Interface for target group entity.
 */
interface TargetGroupInterface
{
    /**
     * Returns the ID of the TargetGroup.
     *
     * @return int
     */
    public function getId();

    /**
     * Returns the title of the TargetGroup.
     *
     * @return string
     */
    public function getTitle();

    /**
     * Sets the title of the TargetGroup.
     *
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * Returns the description of the TargetGroup.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Sets the description of the TargetGroup.
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description);

    /**
     * Returns the priority of the TargetGroup.
     *
     * @return int
     */
    public function getPriority();

    /**
     * Sets the priority of the TargetGroup.
     *
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority);

    /**
     * Returns if the TargetGroup is valid for all webspaces.
     *
     * @return bool
     */
    public function isAllWebspaces();

    /**
     * Sets if the TargetGroup is valid for all webspaces.
     *
     * @param bool $allWebspaces
     *
     * @return $this
     */
    public function setAllWebspaces($allWebspaces);

    /**
     * Returns whether the TargetGroup is active or not.
     *
     * @return bool
     */
    public function isActive();

    /**
     * Decides if the TargetGroup is active or not.
     *
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active);

    /**
     * Returns all Webspaces the TargetGroup is valid for.
     *
     * @return TargetGroupWebspace[]
     */
    public function getWebspaces();

    /**
     * Adds a Webspaces to the TargetGroup.
     *
     * @param TargetGroupWebspaceInterface $webspace
     *
     * @return $this
     */
    public function addWebspace(TargetGroupWebspaceInterface $webspace);

    /**
     * Removes a webspace from the TargetGroup.
     *
     * @param TargetGroupWebspaceInterface $webspace
     *
     * @return $this
     */
    public function removeWebspace(TargetGroupWebspaceInterface $webspace);

    /**
     * Clears all webspaces from this TargetGroup.
     */
    public function clearWebspaces();

    /**
     * Returns the rules, which have to match in order to be assigned to this TargetGroup.
     *
     * @return TargetGroupRuleInterface[]
     */
    public function getRules();

    /**
     * Adds a new rule for this TargetGroup.
     *
     * @param TargetGroupRuleInterface $rule
     *
     * @return $this
     */
    public function addRule(TargetGroupRuleInterface $rule);

    /**
     * Removes a rule from this TargetGroup.
     *
     * @param TargetGroupRuleInterface $rule
     *
     * @return $this
     */
    public function removeRule(TargetGroupRuleInterface $rule);

    /**
     * Clears all rules from this TargetGroup.
     */
    public function clearRules();
}
