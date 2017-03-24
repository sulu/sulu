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
