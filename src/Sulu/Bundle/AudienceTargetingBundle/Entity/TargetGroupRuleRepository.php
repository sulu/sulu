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

use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * Repository class for target group rules.
 *
 * @extends EntityRepository<TargetGroupRuleInterface>
 */
class TargetGroupRuleRepository extends EntityRepository implements TargetGroupRuleRepositoryInterface
{
    /**
     * Saves the given target group rule to the repository.
     *
     * @return TargetGroupRuleInterface
     */
    public function save(TargetGroupRuleInterface $targetGroupRule)
    {
        $this->getEntityManager()->persist($targetGroupRule);

        return $targetGroupRule;
    }
}
