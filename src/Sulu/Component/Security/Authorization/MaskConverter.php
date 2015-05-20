<?php
/*
 * This file is part of the Sulu CMS.
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
        $permissionsData = array(
            'view' => (bool) ($permissions & $this->permissions['view']),
            'add' => (bool) ($permissions & $this->permissions['add']),
            'edit' => (bool) ($permissions & $this->permissions['edit']),
            'delete' => (bool) ($permissions & $this->permissions['delete']),
            'archive' => (bool) ($permissions & $this->permissions['archive']),
            'live' => (bool) ($permissions & $this->permissions['live']),
            'security' => (bool) ($permissions & $this->permissions['security']),
        );

        return $permissionsData;
    }
}
