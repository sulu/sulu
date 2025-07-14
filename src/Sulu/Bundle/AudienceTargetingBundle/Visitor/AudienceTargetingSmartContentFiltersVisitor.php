<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Visitor;

use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Content\Application\Visitor\SmartContentFiltersVisitorInterface;

class AudienceTargetingSmartContentFiltersVisitor implements SmartContentFiltersVisitorInterface
{
    public function __construct(
        private TargetGroupStoreInterface $targetGroupStore,
    ) {
    }

    public function visit(array $data, array $filters, array $parameters): array
    {
        if ($data['audienceTargeting'] ?? null) {
            $filters['audienceTargeting'] = $data['audienceTargeting'];
            $filters['targetGroupId'] = $this->targetGroupStore->getTargetGroupId();
        }

        return $filters;
    }
}
