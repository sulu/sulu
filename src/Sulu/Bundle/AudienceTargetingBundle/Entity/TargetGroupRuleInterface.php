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

/**
 * Interface for target group rule entity.
 */
interface TargetGroupRuleInterface
{
    const FREQUENCY_HIT = 1;

    const FREQUENCY_SESSION = 2;

    const FREQUENCY_VISITOR = 3;

    const FREQUENCY_HIT_NAME = 'hit';

    const FREQUENCY_SESSION_NAME = 'session';

    const FREQUENCY_VISITOR_NAME = 'visitor';

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
     * @param TargetGroupInterface $targetGroup
     *
     * @return $this
     */
    public function setTargetGroup(TargetGroupInterface $targetGroup);

    /**
     * @return TargetGroupConditionInterface[]
     */
    public function getConditions();

    /**
     * @param TargetGroupConditionInterface $condition
     *
     * @return $this
     */
    public function addCondition(TargetGroupConditionInterface $condition);

    /**
     * @param TargetGroupConditionInterface $condition
     *
     * @return $this
     */
    public function removeCondition(TargetGroupConditionInterface $condition);

    /**
     * Clears the rule from its conditions.
     */
    public function clearConditions();
}
