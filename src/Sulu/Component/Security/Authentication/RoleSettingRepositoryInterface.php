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

use Sulu\Component\Persistence\Repository\RepositoryInterface;

interface RoleSettingRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns value of given role-setting.
     *
     * @param int $roleId
     * @param string $key
     *
     * @return mixed|null
     */
    public function findSettingValue($roleId, $key);

    /**
     * Returns role-setting object.
     *
     * @param int $roleId
     * @param string $key
     *
     * @return RoleSettingInterface|null
     */
    public function findSetting($roleId, $key);
}
