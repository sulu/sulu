<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;

/**
 * Repository for the UserSettings, implementing some additional functions
 * for querying objects.
 */
class UserSettingRepository extends EntityRepository implements UserSettingRepositoryInterface
{
}
