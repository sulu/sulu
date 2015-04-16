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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SecurityContextVoter implements VoterInterface
{
    /**
     * The permissions available, defined by config
     * @var array
     */
    private $permissions;

    /**
     * @var AclProviderInterface
     */
    private $aclProvider;

    public function __construct($permissions, AclProviderInterface $aclProvider)
    {
        $this->permissions = $permissions;
        $this->aclProvider = $aclProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array_keys($this->permissions));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $class === SecurityCondition::class || is_subclass_of($class, SecurityCondition::class);
    }

    /**
     * Tests if there is an access control list for the given object
     * @param string $objectId The object to lookup in the access control system
     * @return bool Returns true if an access control list exists for the given object, otherwise false
     */
    public function existsAcl($objectId, $objectType)
    {
        if ($objectId === null || $objectType === null) {
            return false;
        }

        try {
            $this->aclProvider->findAcl(new ObjectIdentity($objectId, $objectType));

            return true;
        } catch (AclNotFoundException $exc) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $attributeVotes = array();

        /** @var User $user */
        $user = $token->getUser();

        if (!is_object($object) ||
            !$this->supportsClass(get_class($object))
        ) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        if ($this->existsAcl($object->getObjectId(), $object->getObjectType()) && $object->getLocale() == null) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $roleVotes = array();
            $groupVotes = array();

            foreach ($user->getUserRoles() as $userRole) {
                // check all given roles if they have the given attribute
                /** @var UserRole $userRole */
                $roleVotes[] = $this->checkPermissions(
                    $object,
                    $attribute,
                    $userRole->getRole()->getPermissions(),
                    $userRole->getLocales()
                );
            }

            foreach ($user->getUserGroups() as $userGroup) {
                // check if one of the user groups have the given attribute
                /** @var UserGroup $userGroup */
                $groupVotes[] = $this->checkUserGroup(
                    $object,
                    $attribute,
                    $userGroup->getGroup(),
                    $userGroup->getLocales()
                );
            }

            // if one of the user's roles or groups is granted access the permission attribute is granted
            $attributeVotes[] = in_array(true, $roleVotes) || in_array(true, $groupVotes);
        }

        // only if all attributes are granted the access is granted
        return in_array(false, $attributeVotes) ? VoterInterface::ACCESS_DENIED : VoterInterface::ACCESS_GRANTED;
    }

    /**
     * Checks if the given group has the permission to execute the desired task
     * @param SecurityCondition $object
     * @param integer $attribute
     * @param Group $group
     * @param array $locales
     * @return bool
     */
    public function checkUserGroup($object, $attribute, Group $group, $locales)
    {
        // check if the group contains the permission
        foreach ($group->getRoles() as $role) {
            /** @var Role $role */
            if ($this->checkPermissions($object, $attribute, $role->getPermissions(), $locales)) {
                return true;
            }
        }

        // check if one of the child group contains the permission
        $children = $group->getChildren();
        if (!empty($children)) {
            foreach ($children as $child) {
                if ($this->checkUserGroup($object, $attribute, $child, $locales)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the given set of permissions grants to execute the desired task
     * @param SecurityCondition $object
     * @param integer $attribute
     * @param Collection $permissions
     * @param array $locales
     * @return bool True if the desired access is valid, otherwise false
     */
    private function checkPermissions($object, $attribute, $permissions, $locales)
    {
        foreach ($permissions as $permission) {
            /** @var Permission $permission */
            if ($this->isGranted($object, $attribute, $permission, $locales)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the combination of permission and userrole is allowed for the given attributes
     * @param SecurityCondition $object
     * @param integer $attribute
     * @param Permission $permission
     * @param array|null $locales
     * @return bool
     */
    private function isGranted($object, $attribute, Permission $permission, $locales)
    {
        if (!is_array($locales)) {
            $locales = array();
        }

        $hasContext = $permission->getContext() == $object->getSecurityContext();
        $hasLocale = $object->getLocale() == null || in_array($object->getLocale(), $locales);

        // if there is a concrete object we only have to check for the locale and context
        if ($object->getObjectId() || $object->getObjectType()) {
            return $hasContext && $hasLocale;
        }

        $hasPermission = $permission->getPermissions() & $this->permissions[$attribute];

        return $hasContext && $hasPermission && $hasLocale;
    }
}
