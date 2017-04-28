<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\UserContext;

interface UserContextStoreInterface
{
    /**
     * Ses the id of the current TargetGroup from the current request.
     *
     * @param string $userContext
     *
     * @return string
     */
    public function setUserContext($userContext);

    /**
     * Returns the id of the current TargetGroup from the current request.
     *
     * @return string
     */
    public function getUserContext();

    /**
     * Sets the given user context as the new one, and marking this value as changed.
     *
     * @param $userContext
     *
     * @return string
     */
    public function updateUserContext($userContext);

    /**
     * Returns whether the value hold by this store has changed or not.
     *
     * @return bool
     */
    public function hasChanged();
}
