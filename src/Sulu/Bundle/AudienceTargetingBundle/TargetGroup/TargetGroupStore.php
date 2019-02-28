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
    /**
     * @var string
     */
    private $targetGroupId;

    /**
     * @var bool
     */
    private $changedTargetGroup = false;

    /**
     * @var bool
     */
    private $influencedContent = false;

    /**
     * {@inheritdoc}
     */
    public function setTargetGroupId($targetGroupId)
    {
        $this->targetGroupId = $targetGroupId;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetGroupId($internal = false)
    {
        if (!$internal) {
            $this->influencedContent = true;
        }

        return $this->targetGroupId;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTargetGroupId($targetGroupId)
    {
        $this->changedTargetGroup = $this->targetGroupId != $targetGroupId;
        $this->setTargetGroupId($targetGroupId);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChangedTargetGroup()
    {
        return $this->changedTargetGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function hasInfluencedContent()
    {
        return $this->influencedContent;
    }
}
