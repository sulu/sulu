<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;

/**
 * An implementation of the AccessControlManagerInterface, which supports registering AccessControlProvider. All method
 * calls are delegated to the AccessControlProvider supporting the given type.
 */
class AccessControlManager implements AccessControlManagerInterface
{
    /**
     * @var AccessControlProviderInterface[]
     */
    protected $accessControlProviders = [];

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    public function __construct(MaskConverterInterface $maskConverter)
    {
        $this->maskConverter = $maskConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        $accessControlProvider = $this->getAccessControlProvider($type);

        if (!$accessControlProvider) {
            return;
        }

        $accessControlProvider->setPermissions($type, $identifier, $permissions);
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($type, $identifier)
    {
        $accessControlProvider = $this->getAccessControlProvider($type);

        if (!$accessControlProvider) {
            return;
        }

        return $accessControlProvider->getPermissions($type, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissions(SecurityCondition $securityCondition, UserInterface $user)
    {
        $objectPermissions = $this->getUserObjectPermission($securityCondition, $user);
        $checkPermissionType = empty($objectPermissions);

        $securityContextPermissions = $this->getUserSecurityContextPermissions(
            $securityCondition,
            $user,
            $checkPermissionType
        );

        if ($checkPermissionType) {
            return $securityContextPermissions;
        }

        return $this->restrictPermissions($objectPermissions, $securityContextPermissions);
    }

    /**
     * Adds a new AccessControlProvider.
     *
     * @param AccessControlProviderInterface $accessControlProvider The AccessControlProvider to add
     */
    public function addAccessControlProvider(AccessControlProviderInterface $accessControlProvider)
    {
        $this->accessControlProviders[] = $accessControlProvider;
    }

    /**
     * Returns the permissions for the given object for the given user.
     *
     * @param SecurityCondition $securityCondition The condition to check
     * @param UserInterface $user The user for the check
     *
     * @return array
     */
    private function getUserObjectPermission(SecurityCondition $securityCondition, UserInterface $user)
    {
        $userPermission = [];
        $permissions = $this->getPermissions($securityCondition->getObjectType(), $securityCondition->getObjectId());

        if (empty($permissions)) {
            return [];
        }

        $roles = $user->getRoleObjects();

        foreach ($roles as $role) {
            $roleId = $role->getId();
            if (!isset($permissions[$roleId])) {
                continue;
            }

            $userPermission = $this->cumulatePermissions($userPermission, $permissions[$roleId]);
        }

        return $userPermission;
    }

    private function getUserSecurityContextPermissions(
        SecurityCondition $securityCondition,
        UserInterface $user,
        $checkPermissionType
    ) {
        $userPermissions = [];

        foreach ($user->getUserRoles() as $userRole) {
            $userPermissions = $this->cumulatePermissions(
                $userPermissions,
                $this->getUserRoleSecurityContextPermission(
                    $securityCondition,
                    $userRole,
                    $checkPermissionType
                )
            );
        }

        return $userPermissions;
    }

    private function getUserRoleSecurityContextPermission(
        SecurityCondition $securityCondition,
        UserRole $userRole,
        $checkPermissionType
    ) {
        $userPermission = $this->maskConverter->convertPermissionsToArray(0);
        $locale = $securityCondition->getLocale();

        foreach ($userRole->getRole()->getPermissions() as $permission) {
            $hasContext = $permission->getContext() == $securityCondition->getSecurityContext();

            if (!$hasContext) {
                continue;
            }

            $hasLocale = $locale == null || in_array($locale, $userRole->getLocales());

            if ($checkPermissionType) {
                $userPermission = $this->maskConverter->convertPermissionsToArray($permission->getPermissions());
            } elseif($hasLocale) {
                array_walk($userPermission, function(&$permission) {
                    $permission = true;
                });
            }
        }

        return $userPermission;
    }


    /**
     * @param array $userPermission
     * @param array $permissions
     * @return array
     */
    private function mapPermissions(array $userPermission, array $permissions, callable $reduce)
    {
        foreach ($permissions as $attribute => $value) {
            if (!isset($userPermission[$attribute])) {
                $userPermission[$attribute] = false;
            }

            $userPermission[$attribute] = $reduce($userPermission[$attribute], $value);
        }

        return $userPermission;
    }
    /**
     * Merges all the true values for the given permission arrays.
     *
     * @param array $permissions The array of the additional permissions
     * @param array $userPermission The array of the currently changing permissions
     *
     * @return mixed
     */
    private function cumulatePermissions(array $userPermission, array $permissions)
    {
        return $this->mapPermissions($userPermission, $permissions, function($permission1, $permission2) {
            return $permission1 || $permission2;
        });
    }

    private function restrictPermissions(array $userPermission, array $permissions)
    {
        return $this->mapPermissions($userPermission, $permissions, function($permission1, $permission2) {
            return $permission1 && $permission2;
        });
    }

    /**
     * Returns the AccessControlProvider, which supports the given type.
     *
     * @param string $type The type the AccessControlProvider should support
     *
     * @return AccessControlProviderInterface
     */
    private function getAccessControlProvider($type)
    {
        foreach ($this->accessControlProviders as $accessControlProvider) {
            if ($accessControlProvider->supports($type)) {
                return $accessControlProvider;
            }
        }
    }
}
