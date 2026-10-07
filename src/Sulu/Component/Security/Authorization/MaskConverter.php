<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

/**
 * A helper class to convert the mask between the numerical and array representation.
 * Also offered as a service by this bundle.
 */
class MaskConverter implements MaskConverterInterface
{
    /**
     * @param array<string, int> $permissions
     */
    public function __construct(
        /**
         * The permissions available, defined by config.
         */
        protected $permissions
    ) {
    }

    public function convertPermissionsToNumber($permissionsData)
    {
        $permissions = 0;

        foreach ($permissionsData as $key => $permission) {
            if ($permission) {
                $permissions |= $this->permissions[$key];
            }
        }

        return $permissions;
    }

    public function convertPermissionsToArray($permissions)
    {
        $permissionsData = [];
        foreach ($this->permissions as $key => $permissionValue) {
            $permissionsData[$key] = (bool) ($permissions & $permissionValue);
        }

        return $permissionsData;
    }
}
