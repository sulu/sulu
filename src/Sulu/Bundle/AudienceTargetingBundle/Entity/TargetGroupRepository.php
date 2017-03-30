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

use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

/**
 * Repository class for target groups.
 */
class TargetGroupRepository extends EntityRepository implements TargetGroupRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function save($targetGroup)
    {
        $newWebspaces = $targetGroup->getWebspaces()->toArray();
        $newRules = $targetGroup->getRules()->toArray();
        $targetGroup = $this->getEntityManager()->merge($targetGroup);

        $oldRules = $targetGroup->getRules()->toArray();
        $newRuleIds = [];
        $targetGroup->clearRules();
        foreach ($newRules as $rule) {
            $targetGroup->addRule($this->getEntityManager()->merge($rule));
            $newRuleIds[] = $rule->getId();
        }

        foreach ($oldRules as $rule) {
            if (!in_array($rule->getId(), $newRuleIds)) {
                $this->getEntityManager()->remove($rule);
            }
        }

        $oldWebspaces = $targetGroup->getWebspaces()->toArray();
        $newWebspaceIds = [];
        $targetGroup->clearWebspaces();
        foreach ($newWebspaces as $webspace) {
            $targetGroup->addWebspace($this->getEntityManager()->merge($webspace));
            $newWebspaceIds[] = $webspace->getId();
        }

        foreach ($oldWebspaces as $webspace) {
            if (!in_array($webspace->getId(), $newWebspaceIds)) {
                $this->getEntityManager()->remove($webspace);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAllActiveForWebspaceOrderedByPriority($webspace)
    {
        $query = $this->createQueryBuilder('targetGroup')
            ->addSelect('targetGroupRules')
            ->addSelect('targetGroupConditions')
            ->join('targetGroup.rules', 'targetGroupRules')
            ->join('targetGroupRules.conditions', 'targetGroupConditions')
            ->leftJoin('targetGroup.webspaces', 'targetGroupWebspaces')
            ->where('targetGroup.active = true')
            ->andWhere('(targetGroup.allWebspaces = true OR targetGroupWebspaces.webspaceKey = :webspace)')
            ->orderBy('targetGroup.priority', 'desc')
            ->getQuery();

        return $query->setParameter('webspace', $webspace)->getResult();
    }
}
