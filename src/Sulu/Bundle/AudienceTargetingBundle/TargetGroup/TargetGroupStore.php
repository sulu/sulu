<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\TargetGroup;

/**
 * This class stores the current user context. Also allows updating it, in which case it is marked as changed.
 */
class TargetGroupStore implements TargetGroupStoreInterface
{
    /**
     * @var string
     */
    private $targetGroup;

    /**
     * @var bool
     */
    private $changed = false;

    /**
     * {@inheritdoc}
     */
    public function setTargetGroupId($targetGroupId)
    {
        $this->targetGroup = $targetGroupId;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetGroupId()
    {
        return $this->targetGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function updateTargetGroupId($targetGroupid)
    {
        $this->changed = $this->getTargetGroupId() != $targetGroupid;
        $this->setTargetGroupId($targetGroupid);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChanged()
    {
        return $this->changed;
    }
}
