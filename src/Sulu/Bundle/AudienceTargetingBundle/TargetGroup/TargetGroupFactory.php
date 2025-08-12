<?php

declare(strict_types=1);

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
            $targetGroup = $this->entityManager->getPartialReference(
                TargetGroupInterface::class,
                $targetGroupId,
            );

            $targetGroups[] = $targetGroup;
        }

        return $targetGroups;
    }
}
