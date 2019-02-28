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
 * Repository class for target groups webspaces.
 */
class TargetGroupWebspaceRepository extends EntityRepository implements TargetGroupWebspaceRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function findOrCreate(TargetGroupInterface $targetGroup, $webspaceKey)
    {
        $targetGroupWebspace = null;

        if ($targetGroup->getId()) {
            $targetGroupWebspace = $this->findOneBy(
                [
                    'targetGroup' => $targetGroup,
                    'webspaceKey' => $webspaceKey,
                ]
            );
        }

        if (!$targetGroupWebspace) {
            $targetGroupWebspace = $this->createNew();
        }

        return $targetGroupWebspace;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(TargetGroupWebspaceInterface $targetGroupWebspace)
    {
        $this->getEntityManager()->remove($targetGroupWebspace);
    }
}
