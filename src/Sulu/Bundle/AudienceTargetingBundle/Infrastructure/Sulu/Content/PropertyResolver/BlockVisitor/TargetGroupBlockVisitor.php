<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor;

use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\BlockVisitorInterface;

class TargetGroupBlockVisitor implements BlockVisitorInterface
{
    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    public function __construct(TargetGroupStoreInterface $targetGroupStore)
    {
        $this->targetGroupStore = $targetGroupStore;
    }

    public function visit(array $block): ?array
    {
        $blockPropertyTypeSettings = $block['settings'] ?? [];

        if (\is_array($blockPropertyTypeSettings)
            && isset($blockPropertyTypeSettings['target_groups_enabled'])
            && $blockPropertyTypeSettings['target_groups_enabled']
            && isset($blockPropertyTypeSettings['target_groups'])
            /** @var string[] */
            && !\in_array($this->targetGroupStore->getTargetGroupId(), $blockPropertyTypeSettings['target_groups'])
        ) {
            return null;
        } else {
            return $block;
        }
    }
}
