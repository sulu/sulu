<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Permission;

/**
 * A helper class to convert the mask between the numerical and array representation.
 * Also offered as a service by this bundle.
 */
class MaskConverter
{
    const VIEW = 64;
    const ADD = 32;
    const EDIT = 16;
    const DELETE = 8;
    const ARCHIVE = 4;
    const LIVE = 2;
    const SECURITY = 1;

    /**
     * Converts a permissions array to a bit field
     * @param array $permissionsData
     * @return int
     */
    public function convertPermissionsToNumber($permissionsData)
    {
        $permissions = 0;

        foreach ($permissionsData as $key => $permission) {
            if ($permission) {
                switch ($key) {
                    case 'view':
                        $permissions |= self::VIEW;
                        break;
                    case 'add':
                        $permissions |= self::ADD;
                        break;
                    case 'edit':
                        $permissions |= self::EDIT;
                        break;
                    case 'delete':
                        $permissions |= self::DELETE;
                        break;
                    case 'archive':
                        $permissions |= self::ARCHIVE;
                        break;
                    case 'live':
                        $permissions |= self::LIVE;
                        break;
                    case 'security':
                        $permissions |= self::SECURITY;
                        break;
                }
            }
        }

        return $permissions;
    }

    /**
     * Converts the given permissions from the numerical to the array representation
     * @param int $permissions
     * @return array
     */
    public function convertPermissionsToArray($permissions)
    {
        $permissionsData = array(
            'view' => (bool)($permissions & self::VIEW),
            'add' => (bool)($permissions & self::ADD),
            'edit' => (bool)($permissions & self::EDIT),
            'delete' => (bool)($permissions & self::DELETE),
            'archive' => (bool)($permissions & self::ARCHIVE),
            'live' => (bool)($permissions & self::LIVE),
            'security' => (bool)($permissions & self::SECURITY == self::SECURITY),
        );

        return $permissionsData;
    }
}
