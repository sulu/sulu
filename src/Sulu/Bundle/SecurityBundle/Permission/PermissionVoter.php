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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Security\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoter implements VoterInterface
{
    const SECURITY_CONTEXT_CLASS = 'Sulu\Bundle\SecurityBundle\Security\SecurityContext';

    /**
     * The permissions avaiable, defined by config
     * @var array
     */
    protected $permissions;

    public function __construct($permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param string $attribute An attribute
     * @return Boolean true if this Voter supports the attribute, false otherwise
     */
    public function supportsAttribute($attribute)
    {
        if (!is_array($attribute) || !isset($attribute['permission'])) {
            return false;
        }

        return true;
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     * @return Boolean true if this Voter can process the class
     */
    public function supportsClass($class)
    {
        return $class === self::SECURITY_CONTEXT_CLASS || is_subclass_of($class, self::SECURITY_CONTEXT_CLASS);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param SecurityContext $object     The object to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $vote = VoterInterface::ACCESS_DENIED;
        /** @var User $user */
        $user = $token->getUser();

        // if not our attribute or class, we can't decide
        if (!is_object($object) ||
            !$this->supportsClass(get_class($object)) ||
            !$this->supportsAttribute($attributes)
        ) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($user->getUserRoles() as $userRole) {
            // check all given roles if they have the given attribute
            /** @var UserRole $userRole */
            if ($this->checkPermissions($object, $attributes, $userRole->getRole()->getPermissions(), $userRole->getLocales())) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        foreach ($user->getUserGroups() as $userGroup) {
            // check if one of the user groups have the given attribute
            /** @var UserGroup $userGroup */
            if ($this->checkUserGroup($object, $attributes, $userGroup->getGroup(), $userGroup->getLocales())) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $vote;
    }

    /**
     * Checks if the given group has the permission to execute the desired task
     * @param SecurityContext $object
     * @param array $attributes
     * @param Group $group
     * @param array $locales
     * @return bool
     */
    public function checkUserGroup($object, $attributes, Group $group, $locales)
    {
        // check if the group contains the permission
        foreach ($group->getRoles() as $role) {
            /** @var Role $role */
            if ($this->checkPermissions($object, $attributes, $role->getPermissions(), $locales)) {
                return true;
            }
        }

        // check if one of the child group contains the permission
        $children = $group->getChildren();
        if (!empty($children)) {
            foreach ($children as $child) {
                if ($this->checkUserGroup($object, $attributes, $child, $locales)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the given set of permissions grants to execute the desired task
     * @param SecurityContext $object
     * @param array $attributes
     * @param Collection $permissions
     * @param array $locales
     * @return bool True if the desired access is valid, otherwise false
     */
    private function checkPermissions($object, $attributes, $permissions, $locales)
    {
        foreach ($permissions as $permission) {
            /** @var Permission $permission */
            if ($this->isGranted($object, $attributes, $permission, $locales)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the combination of permission and userrole is allowed for the given attributes
     * @param SecurityContext $object
     * @param array $attributes
     * @param Permission $permission
     * @param array|null $locales
     * @return bool
     */
    private function isGranted($object, array $attributes, Permission $permission, $locales)
    {
        $hasContext = $permission->getContext() == $object->getSecurityContext();

        $hasPermission = $permission->getPermissions() & $this->permissions[$attributes['permission']];

        $hasLocale = !(isset($attributes['locale']) && is_array($locales)) || in_array($attributes['locale'], $locales);

        return $hasContext && $hasPermission && $hasLocale;
    }
}
