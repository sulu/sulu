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
 * Interface for target group repository.
 */
interface TargetGroupRepositoryInterface extends RepositoryInterface
{
    /**
     * Saves the given target group to the repository.
     *
     * @param TargetGroupInterface $targetGroup
     *
     * @return TargetGroupInterface
     */
    public function save(TargetGroupInterface $targetGroup);

    /**
     * Find the target groups with the given IDs.
     *
     * @param int[] $ids
     *
     * @return TargetGroupInterface[]
     */
    public function findByIds($ids);

    /**
     * Returns all active TargetGroups from the given webspace ordered by their priority.
     *
     * Takes a value from the FREQUENCY_* constant of the TargetGroupRuleInterface as second argument. This parameter
     * describes which is the highest frequency which will be taken into account when evaluating.
     *
     * @param string $webspace
     * @param int $maxFrequency
     *
     * @return TargetGroupInterface[]
     */
    public function findAllActiveForWebspaceOrderedByPriority(
        $webspace,
        $maxFrequency = TargetGroupRuleInterface::FREQUENCY_VISITOR
    );
}
