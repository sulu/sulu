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
 * Repository class for target groups.
 */
class TargetGroupRepository extends EntityRepository implements TargetGroupRepositoryInterface
{
    public function save(TargetGroupInterface $targetGroup)
    {
        $this->getEntityManager()->persist($targetGroup);

        return $targetGroup;
    }

    public function findByIds($ids)
    {
        $query = $this->createQueryBuilder('targetGroup')
            ->where('targetGroup.id IN (:ids)')
            ->getQuery();

        return $query->setParameter('ids', $ids)->getResult();
    }

    public function findAllActiveForWebspaceOrderedByPriority(
        $webspace,
        $maxFrequency = TargetGroupRuleInterface::FREQUENCY_VISITOR
    ) {
        $query = $this->createQueryBuilder('targetGroup')
            ->addSelect('targetGroupRules')
            ->addSelect('targetGroupConditions')
            ->join('targetGroup.rules', 'targetGroupRules')
            ->join('targetGroupRules.conditions', 'targetGroupConditions')
            ->leftJoin('targetGroup.webspaces', 'targetGroupWebspaces')
            ->where('targetGroup.active = true')
            ->andWhere('(targetGroup.allWebspaces = true OR targetGroupWebspaces.webspaceKey = :webspace)')
            ->andWhere('targetGroupRules.frequency <= :maxFrequency')
            ->orderBy('targetGroup.priority', 'desc')
            ->getQuery();

        return $query->setParameter('webspace', $webspace)
            ->setParameter('maxFrequency', $maxFrequency)
            ->getResult();
    }
}
