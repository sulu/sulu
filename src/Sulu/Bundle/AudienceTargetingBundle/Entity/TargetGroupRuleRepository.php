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
 */
class TargetGroupRuleRepository extends EntityRepository implements TargetGroupRuleRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save(TargetGroupRuleInterface $targetGroupRule)
    {
        $newConditions = [];
        foreach ($targetGroupRule->getConditions()->toArray() as $condition) {
            $condition = $this->getEntityManager()->merge($condition);
            $newConditions[] = $condition;
        }

        $targetGroupRule->clearConditions();
        $targetGroupRule = $this->getEntityManager()->merge($targetGroupRule);

        foreach ($targetGroupRule->getConditions()->toArray() as $condition) {
            if (!in_array($condition, $newConditions)) {
                $targetGroupRule->removeCondition($condition);
                $this->getEntityManager()->remove($condition);
            }
        }

        foreach ($newConditions as $newCondition) {
            if (!$targetGroupRule->getConditions()->contains($newCondition)) {
                $targetGroupRule->addCondition($newCondition);
            }
            $newCondition->setRule($targetGroupRule);
        }

        return $targetGroupRule;
    }
}
