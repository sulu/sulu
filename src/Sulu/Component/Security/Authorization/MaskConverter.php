<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * The permissions available, defined by config.
     *
     * @var array
     */
    protected $permissions;

    public function __construct($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function convertPermissionsToArray($permissions)
    {
        $permissionsData = [
            PermissionTypes::VIEW => (bool) ($permissions & $this->permissions[PermissionTypes::VIEW]),
            PermissionTypes::ADD => (bool) ($permissions & $this->permissions[PermissionTypes::ADD]),
            PermissionTypes::EDIT => (bool) ($permissions & $this->permissions[PermissionTypes::EDIT]),
            PermissionTypes::DELETE => (bool) ($permissions & $this->permissions[PermissionTypes::DELETE]),
            PermissionTypes::ARCHIVE => (bool) ($permissions & $this->permissions[PermissionTypes::ARCHIVE]),
            PermissionTypes::LIVE => (bool) ($permissions & $this->permissions[PermissionTypes::LIVE]),
            PermissionTypes::SECURITY => (bool) ($permissions & $this->permissions[PermissionTypes::SECURITY]),
        ];

        return $permissionsData;
    }
}
