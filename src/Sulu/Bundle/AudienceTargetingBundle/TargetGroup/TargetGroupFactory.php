<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\TargetGroup;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Content\Domain\Factory\TargetGroupFactoryInterface;

class TargetGroupFactory implements TargetGroupFactoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function create(array $targetGroupIds): array
    {
        if (empty($targetGroupIds)) {
            return [];
        }

        $targetGroups = [];
        foreach ($targetGroupIds as $targetGroupId) {
            /** @var TargetGroupInterface $targetGroup */
            $targetGroup = $this->entityManager->getReference(
                TargetGroupInterface::class,
                $targetGroupId,
            );

            $targetGroups[] = $targetGroup;
        }

        return $targetGroups;
    }
}
