<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserSettingManager;

interface UserSettingManagerInterface
{
    /**
     * Removes setting for all users by key and value.
     *
     * @param string $key
     * @param mixed $value
     */
    public function removeSettings($key, $value);
}
