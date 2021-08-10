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
 * Interface for target group rule entity.
 */
interface TargetGroupRuleInterface
{
    public const FREQUENCY_HIT = 1;

    public const FREQUENCY_SESSION = 2;

    public const FREQUENCY_VISITOR = 3;

    public const FREQUENCY_HIT_NAME = 'hit';

    public const FREQUENCY_SESSION_NAME = 'session';

    public const FREQUENCY_VISITOR_NAME = 'visitor';

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
     * @return int
     */
    public function getFrequency();

    /**
     * @param int $frequency
     *
     * @return $this
     */
    public function setFrequency($frequency);

    /**
     * @return TargetGroupInterface
     */
    public function getTargetGroup();

    /**
     * @return $this
     */
    public function setTargetGroup(TargetGroupInterface $targetGroup);

    /**
     * @return TargetGroupConditionInterface[]
     */
    public function getConditions();

    /**
     * @return $this
     */
    public function addCondition(TargetGroupConditionInterface $condition);

    /**
     * @return $this
     */
    public function removeCondition(TargetGroupConditionInterface $condition);

    /**
     * Clears the rule from its conditions.
     */
    public function clearConditions();
}
