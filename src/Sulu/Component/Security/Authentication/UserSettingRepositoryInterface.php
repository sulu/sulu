<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Doctrine\Persistence\ObjectRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;

/**
 * Describes how the user settings are retrieved from the database.
 *
 * @extends ObjectRepository<UserSetting>
 */
interface UserSettingRepositoryInterface extends ObjectRepository
{
}
