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
     * @param bool $internal True for internal calls, which means the result has no influence on the page output
     *
     * @return string
     */
    public function getTargetGroupId($internal = false);

    /**
     * Sets the given target group as the new one, and marking this value as changed.
     *
     * @return string
     */
    public function updateTargetGroupId($targetGroupId);

    /**
     * Returns whether the value hold by this store has changed or not.
     *
     * @return bool
     */
    public function hasChangedTargetGroup();

    /**
     * Returns whether the content from this request has been influenced by the target group or not.
     *
     * @return bool
     */
    public function hasInfluencedContent();
}
