<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Rule;

use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;

/**
 * Finds the correct target group based on the given rules.
 */
interface TargetGroupEvaluatorInterface
{
    /**
     * Returns the target group which matches the current circumstances.
     *
     * @return TargetGroupInterface
     */
    public function evaluate();
}
