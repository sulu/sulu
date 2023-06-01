<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\TargetGroup;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;

/**
 * Finds the correct target group based on the given rules.
 */
interface TargetGroupEvaluatorInterface
{
    /**
     * Returns the target group which matches the current circumstances.
     *
     * Takes a value from the FREQUENCY_* constant of the TargetGroupRuleInterface. This parameter describes which is
     * the highest frequency which will be taken into account when evaluating.
     *
     * @param int $maxFrequency
     * @param TargetGroupInterface $currentTargetGroup
     *
     * @return TargetGroupInterface
     */
    public function evaluate(
        $maxFrequency = TargetGroupRuleInterface::FREQUENCY_VISITOR,
        ?TargetGroupInterface $currentTargetGroup = null
    );
}
