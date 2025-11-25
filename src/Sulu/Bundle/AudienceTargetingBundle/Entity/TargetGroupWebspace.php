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
    private int $id;

    private string $webspaceKey;

    private TargetGroupInterface $targetGroup;

    public function getId(): int
    {
        return $this->id;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey(string $webspaceKey): static
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function getTargetGroup(): TargetGroupInterface
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(TargetGroupInterface $targetGroup): static
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }
}
