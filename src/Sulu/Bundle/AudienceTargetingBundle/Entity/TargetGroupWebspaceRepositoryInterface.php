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
 * Interface for target group webspace repository.
 *
 * @extends RepositoryInterface<TargetGroupWebspaceInterface>
 */
interface TargetGroupWebspaceRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds a target-group-webspace by given target-group and webspace-key or creates it.
     *
     * @param string $webspaceKey
     *
     * @return TargetGroupWebspaceInterface
     */
    public function findOrCreate(TargetGroupInterface $targetGroup, $webspaceKey);

    /**
     * Removes a target group webspace from database.
     */
    public function remove(TargetGroupWebspaceInterface $targetGroupWebspace);
}
