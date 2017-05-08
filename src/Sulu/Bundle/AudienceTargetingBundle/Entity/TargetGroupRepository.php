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
    public function save(TargetGroupInterface $targetGroup)
    {
        $newRules = [];
        foreach ($targetGroup->getRules()->toArray() as $rule) {
            $rule = $this->getEntityManager()->getRepository(TargetGroupRuleInterface::class)->save($rule);
            $newRules[] = $rule;
        }

        $newWebspaces = [];
        foreach ($targetGroup->getWebspaces()->toArray() as $webspace) {
            $newWebspace = $this->getEntityManager()->merge($webspace);
            $newWebspaces[] = $newWebspace;
        }

        $targetGroup->clearRules();
        $targetGroup->clearWebspaces();
        $targetGroup = $this->getEntityManager()->merge($targetGroup);

        foreach ($targetGroup->getRules()->toArray() as $rule) {
            if (!in_array($rule, $newRules)) {
                $targetGroup->removeRule($rule);
                $this->getEntityManager()->remove($rule);
            }
        }

        foreach ($targetGroup->getWebspaces()->toArray() as $webspace) {
            if (!in_array($webspace, $newWebspaces)) {
                $targetGroup->removeWebspace($webspace);
                $this->getEntityManager()->remove($webspace);
            }
        }

        foreach ($newRules as $newRule) {
            if (!$targetGroup->getRules()->contains($newRule)) {
                $targetGroup->addRule($newRule);
            }
            $newRule->setTargetGroup($targetGroup);
        }

        foreach ($newWebspaces as $newWebspace) {
            if (!$targetGroup->getWebspaces()->contains($newWebspace)) {
                $targetGroup->addWebspace($newWebspace);
            }
            $newWebspace->setTargetGroup($targetGroup);
        }

        return $targetGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function findByIds($ids)
    {
        $query = $this->createQueryBuilder('targetGroup')
            ->where('targetGroup.id IN (:ids)')
            ->getQuery();

        return $query->setParameter('ids', $ids)->getResult();
    }

    /**
     * {@inheritdoc}
     */
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
