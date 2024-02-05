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

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Interface for target group rule repository.
 *
 * @extends RepositoryInterface<TargetGroupRuleInterface>
 */
interface TargetGroupRuleRepositoryInterface extends RepositoryInterface
{
    /**
     * Saves the rule of the target group rule to the database.
     *
     * @return TargetGroupRuleInterface
     */
    public function save(TargetGroupRuleInterface $targetGroupRule);
}
