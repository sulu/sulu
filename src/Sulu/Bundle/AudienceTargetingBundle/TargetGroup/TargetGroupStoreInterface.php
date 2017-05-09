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

interface TargetGroupStoreInterface
{
    /**
     * Sets the id of the current TargetGroup from the current request.
     *
     * @param string $targetGroupId
     *
     * @return string
     */
    public function setTargetGroupId($targetGroupId);

    /**
     * Returns the id of the current TargetGroup from the current request.
     *
     * @return string
     */
    public function getTargetGroupId();

    /**
     * Sets the given target group as the new one, and marking this value as changed.
     *
     * @param $targetGroupid
     *
     * @return string
     */
    public function updateTargetGroupId($targetGroupid);

    /**
     * Returns whether the value hold by this store has changed or not.
     *
     * @return bool
     */
    public function hasChanged();
}
