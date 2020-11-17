<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Content\Types\Block;

use Sulu\Bundle\AudienceTargetingBundle\TargetGroup\TargetGroupStoreInterface;
use Sulu\Component\Content\Compat\Block\BlockPropertyType;
use Sulu\Component\Content\Types\Block\BlockSkipperInterface;

class TargetGroupBlockSkipper implements BlockSkipperInterface
{
    /**
     * @var TargetGroupStoreInterface
     */
    private $targetGroupStore;

    public function __construct(TargetGroupStoreInterface $targetGroupStore)
    {
        $this->targetGroupStore = $targetGroupStore;
    }

    public function shouldSkip(BlockPropertyType $block): bool
    {
        $blockPropertyTypeSettings = $block->getSettings();

        return \is_array($blockPropertyTypeSettings)
            && isset($blockPropertyTypeSettings['target_groups_enabled'])
            && $blockPropertyTypeSettings['target_groups_enabled']
            && isset($blockPropertyTypeSettings['target_groups'])
            && !\in_array($this->targetGroupStore->getTargetGroupId(), $blockPropertyTypeSettings['target_groups']);
    }
}
