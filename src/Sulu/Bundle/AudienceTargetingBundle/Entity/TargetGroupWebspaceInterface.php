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
 * Interface for target group webspace definition.
 */
interface TargetGroupWebspaceInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getWebspaceKey();

    /**
     * @param string $webspaceKey
     *
     * @return $this
     */
    public function setWebspaceKey($webspaceKey);

    /**
     * @return TargetGroupInterface
     */
    public function getTargetGroup();

    /**
     * @param TargetGroupInterface $targetGroup
     *
     * @return $this
     */
    public function setTargetGroup(TargetGroupInterface $targetGroup);
}
