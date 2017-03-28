<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\Collection;

/**
 * Interface for target group entity.
 */
interface TargetGroupInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param int $priority
     *
     * @return $this
     */
    public function setPriority($priority);

    /**
     * @return bool
     */
    public function isAllWebspaces();

    /**
     * @param bool $allWebspaces
     *
     * @return $this
     */
    public function setAllWebspaces($allWebspaces);

    /**
     * @return bool
     */
    public function isActive();

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active);

    /**
     * @return Collection
     */
    public function getWebspaces();

    /**
     * @param TargetGroupWebspaceInterface $webspace
     *
     * @return $this
     */
    public function addWebspace(TargetGroupWebspaceInterface $webspace);

    /**
     * @param TargetGroupWebspaceInterface $webspace
     *
     * @return $this
     */
    public function removeWebspace(TargetGroupWebspaceInterface $webspace);

    /**
     * @return TargetGroupRuleInterface[]
     */
    public function getRules();

    /**
     * @param TargetGroupRuleInterface $rule
     *
     * @return $this
     */
    public function addRule(TargetGroupRuleInterface $rule);

    /**
     * @param TargetGroupRuleInterface $rule
     *
     * @return $this
     */
    public function removeRule(TargetGroupRuleInterface $rule);
}
