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

use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Sulu\Component\Security\Event\SecurityEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(MaskConverterInterface $maskConverter, EventDispatcherInterface $eventDispatcher)
    {
        $this->maskConverter = $maskConverter;
        $this->eventDispatcher = $eventDispatcher;
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

        $this->eventDispatcher->dispatch(
            SecurityEvents::PERMISSION_UPDATE,
            new PermissionUpdateEvent($type, $identifier, $permissions)
        );
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
            $securityCondition->getLocale(),
            $securityCondition->getSecurityContext(),
            $user,
            $checkPermissionType
        );

        if ($checkPermissionType) {
            return $securityContextPermissions;
        }

        return $this->restrictPermissions($objectPermissions, $securityContextPermissions);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserPermissionByArray($locale, $securityContext, $objectPermissionsByRole, UserInterface $user)
    {
        $objectPermissions = $this->getUserObjectPermissionByArray($objectPermissionsByRole, $user);
        $checkPermissionType = empty($objectPermissions);

        $securityContextPermissions = $this->getUserSecurityContextPermissions(
            $locale,
            $securityContext,
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
        $permissions = $this->getPermissions($securityCondition->getObjectType(), $securityCondition->getObjectId());

        return $this->getUserObjectPermissionByArray($permissions, $user);
    }

    /**
     * Returns the permissions for the given permission array and the given user.
     *
     * @param array $permissions Object permissions
     * @param UserInterface $user The user for the check
     *
     * @return array
     */
    private function getUserObjectPermissionByArray($permissions, UserInterface $user)
    {
        if (empty($permissions)) {
            return [];
        }

        $userPermission = [];
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

    /**
     * Returns the permissions for the given security context for the given user.
     *
     * @param string $locale
     * @param string $securityContext
     * @param UserInterface $user The user for which the security is checked
     * @param bool $checkPermissionType Flag to show if the permission type should also be checked. If set to false
     *                                     it will only check if the user has access to the context in the given locale
     *
     * @return array
     */
    private function getUserSecurityContextPermissions(
        $locale,
        $securityContext,
        UserInterface $user,
        $checkPermissionType
    ) {
        $userPermissions = [];

        foreach ($user->getUserRoles() as $userRole) {
            $userPermissions = $this->cumulatePermissions(
                $userPermissions,
                $this->getUserRoleSecurityContextPermission(
                    $locale,
                    $securityContext,
                    $userRole,
                    $checkPermissionType
                )
            );
        }

        return $userPermissions;
    }

    /**
     * Returns the permissions for the given security context for the given user role.
     *
     * @param string $locale
     * @param string $securityContext
     * @param UserRole $userRole The user role for which the security is checked
     * @param bool $checkPermissionType Flag to show if the permission type should also be checked
     *
     * @return array
     */
    private function getUserRoleSecurityContextPermission(
        $locale,
        $securityContext,
        UserRole $userRole,
        $checkPermissionType
    ) {
        $userPermission = $this->maskConverter->convertPermissionsToArray(0);

        foreach ($userRole->getRole()->getPermissions() as $permission) {
            $hasContext = $permission->getContext() == $securityContext;

            if (!$hasContext) {
                continue;
            }

            $hasLocale = $locale == null || in_array($locale, $userRole->getLocales());

            if (!$hasLocale) {
                continue;
            }

            if ($checkPermissionType) {
                $userPermission = $this->maskConverter->convertPermissionsToArray($permission->getPermissions());
            } else {
                array_walk($userPermission, function (&$permission) {
                    $permission = true;
                });
            }
        }

        return $userPermission;
    }

    /**
     * @param array $userPermission
     * @param array $permissions
     *
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
        return $this->mapPermissions($userPermission, $permissions, function ($permission1, $permission2) {
            return $permission1 || $permission2;
        });
    }

    /**
     * Merges all the values for the given permission arrays. Only returns true if all values are true.
     *
     * @param array $permissions The array of the additional permissions
     * @param array $userPermission The array of the currently changing permissions
     *
     * @return mixed
     */
    private function restrictPermissions(array $userPermission, array $permissions)
    {
        return $this->mapPermissions($userPermission, $permissions, function ($permission1, $permission2) {
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
