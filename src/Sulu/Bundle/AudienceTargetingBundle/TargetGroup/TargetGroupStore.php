<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\TargetGroup;

/**
 * This class stores the current target group. Also allows updating it, in which case it is marked as changed.
 */
class TargetGroupStore implements TargetGroupStoreInterface
{
    private ?string $targetGroupId = null;

    private bool $changedTargetGroup = false;

    private bool $influencedContent = false;

    public function setTargetGroupId($targetGroupId)
    {
        $this->targetGroupId = $targetGroupId;
    }

    public function getTargetGroupId($internal = false)
    {
        if (!$internal) {
            $this->influencedContent = true;
        }

        return $this->targetGroupId;
    }

    public function updateTargetGroupId($targetGroupId)
    {
        $this->changedTargetGroup = $this->targetGroupId != $targetGroupId;
        $this->setTargetGroupId($targetGroupId);
    }

    public function hasChangedTargetGroup()
    {
        return $this->changedTargetGroup;
    }

    public function hasInfluencedContent()
    {
        return $this->influencedContent;
    }
}
