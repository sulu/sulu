<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebspaceKey($webspaceKey)
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetGroup()
    {
        return $this->targetGroup;
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetGroup(TargetGroupInterface $targetGroup)
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }
}
