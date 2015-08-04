<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Doctrine\Common\Persistence\ObjectRepository;

/**
 * Describes how the user settings are retrieved from the database.
 */
interface UserSettingRepositoryInterface extends ObjectRepository
{
    /**
     * Retrieves all settings by key and value
     *
     * @param string $key
     * @param mixed $value
     *
     * @return mixed|null
     */
    public function getSettingsByKeyAndValue($key, $value);
}
