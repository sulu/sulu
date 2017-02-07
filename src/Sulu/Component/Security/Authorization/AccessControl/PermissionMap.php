<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;

/**
 * Holds all the permission possibilities.
 */
class PermissionMap implements PermissionMapInterface
{
    /**
     * @var array
     */
    protected $permissions;

    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getMasks($permission, $object)
    {
        if (!$this->contains($permission)) {
            return;
        }

        return [$this->permissions[$permission]];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($permission)
    {
        return isset($this->permissions[$permission]);
    }
}
