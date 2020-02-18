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

/**
 * Entity class that defines a webspace that is applied to a target group.
 */
class TargetGroupWebspace implements TargetGroupWebspaceInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var TargetGroupInterface
     */
    private $targetGroup;

    public function getId()
    {
        return $this->id;
    }

    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey($webspaceKey)
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(TargetGroupInterface $targetGroup)
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }
}
