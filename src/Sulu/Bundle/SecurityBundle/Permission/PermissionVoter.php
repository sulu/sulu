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

use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class PermissionVoter implements VoterInterface
{
    const USER_CLASS = 'Sulu\Bundle\SecurityBundle\Entity\User';

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
        if (!is_array($attribute) || !isset($attribute['context']) || !isset($attribute['permission'])) {
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
        return $class === self::USER_CLASS || is_subclass_of($class, self::USER_CLASS);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param object $object     The object to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // if not our attribute or class, we can't decide
        if (!is_object($token->getUser()) ||
            !$this->supportsClass(get_class($token->getUser())) ||
            !$this->supportsAttribute($attributes)
        ) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($token->getUser()->getUserRoles() as $userRole) {
            //check all given roles if they have the given attribute
            /** @var UserRole $userRole */
            foreach ($userRole->getRole()->getPermissions() as $permission) {
                /** @var Permission $permission */
                if ($this->isGranted($attributes, $permission, $userRole)
                ) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }

    /**
     * Checks if the combination of permission and userrole is allowed for the given attributes
     * @param array $attributes
     * @param Permission $permission
     * @param UserRole $userRole
     * @return bool
     */
    public function isGranted(array $attributes, Permission $permission, UserRole $userRole)
    {
        $hasContext = $permission->getContext() == $attributes['context'];

        $hasPermission = $permission->getPermissions() & $this->permissions[$attributes['permission']];

        $hasLocale = !isset($attributes['locale']) || in_array($attributes['locale'], $userRole->getLocales());

        return $hasContext && $hasPermission && $hasLocale;
    }
}
