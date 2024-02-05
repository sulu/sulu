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
 *
 * @extends EntityRepository<TargetGroupInterface>
 */
class TargetGroupRepository extends EntityRepository implements TargetGroupRepositoryInterface
{
    /**
     * Saves the given target group to the repository.
     *
     * @return TargetGroupInterface
     */
    public function save(TargetGroupInterface $targetGroup)
    {
        $this->getEntityManager()->persist($targetGroup);

        return $targetGroup;
    }

    public function findByIds($ids)
    {
        $queryBuilder = $this->createQueryBuilder('targetGroup')
            ->where('targetGroup.id IN (:ids)')
            ->setParameter('ids', $ids);

        /** @var TargetGroupInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }

    public function findAllActiveForWebspaceOrderedByPriority(
        $webspace,
        $maxFrequency = TargetGroupRuleInterface::FREQUENCY_VISITOR
    ) {
        $queryBuilder = $this->createQueryBuilder('targetGroup')
            ->addSelect('targetGroupRules')
            ->addSelect('targetGroupConditions')
            ->join('targetGroup.rules', 'targetGroupRules')
            ->join('targetGroupRules.conditions', 'targetGroupConditions')
            ->leftJoin('targetGroup.webspaces', 'targetGroupWebspaces')
            ->where('targetGroup.active = true')
            ->andWhere('(targetGroup.allWebspaces = true OR targetGroupWebspaces.webspaceKey = :webspace)')
            ->setParameter('webspace', $webspace)
            ->andWhere('targetGroupRules.frequency <= :maxFrequency')
            ->setParameter('maxFrequency', $maxFrequency)
            ->orderBy('targetGroup.priority', 'desc');

        /** @var TargetGroupInterface[] */
        return $queryBuilder->getQuery()->getResult();
    }
}
