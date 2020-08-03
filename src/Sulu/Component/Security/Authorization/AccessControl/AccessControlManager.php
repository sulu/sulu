<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
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

    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    public function __construct(
        MaskConverterInterface $maskConverter,
        EventDispatcherInterface $eventDispatcher,
        SystemStoreInterface $systemStore
    ) {
        $this->maskConverter = $maskConverter;
        $this->eventDispatcher = $eventDispatcher;
        $this->systemStore = $systemStore;
    }

    public function setPermissions($type, $identifier, $permissions)
    {
        $accessControlProvider = $this->getAccessControlProvider($type);

        if (!$accessControlProvider) {
            return;
        }

        $accessControlProvider->setPermissions($type, $identifier, $permissions);

        $this->eventDispatcher->dispatch(
            new PermissionUpdateEvent($type, $identifier, $permissions),
            SecurityEvents::PERMISSION_UPDATE
        );
    }

    public function getPermissions($type, $identifier, $system = null)
    {
        $accessControlProvider = $this->getAccessControlProvider($type);

        if (!$accessControlProvider) {
            return;
        }

        return $accessControlProvider->getPermissions($type, $identifier, $system);
    }

    public function getUserPermissions(SecurityCondition $securityCondition, $user)
    {
        if (!$user instanceof UserInterface) {
            return [];
        }

        $system = $this->systemStore->getSystem();
        $locale = $securityCondition->getLocale();

        $objectPermissions = $this->getUserObjectPermission(
            $securityCondition,
            $this->getRolesForLocale($user, $locale),
            $system
        );
        $checkPermissionType = empty($objectPermissions);

        $securityContextPermissions = $this->getRolesSecurityContextPermissions(
            $securityCondition->getSecurityContext(),
            $this->getRolesForLocale($user, $locale),
            $checkPermissionType,
            $system
        );

        if ($checkPermissionType) {
            return $securityContextPermissions;
        }

        return $this->restrictPermissions($objectPermissions, $securityContextPermissions);
    }

    public function getUserPermissionByArray(
        $locale,
        $securityContext,
        $objectPermissionsByRole,
        UserInterface $user
    ) {
        $system = $this->systemStore->getSystem();

        $objectPermissions = $this->getRolesObjectPermissionsByArray(
            $objectPermissionsByRole,
            $this->getRolesForLocale($user, $locale),
            $system
        );
        $checkPermissionType = empty($objectPermissions);

        $securityContextPermissions = $this->getRolesSecurityContextPermissions(
            $securityContext,
            $this->getRolesForLocale($user, $locale),
            $checkPermissionType,
            $system
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
     * @param RoleInterface[] $roles The role for which the security should be checked
     *
     * @return array
     */
    private function getUserObjectPermission(SecurityCondition $securityCondition, array $roles, $system)
    {
        $permissions = $this->getPermissions(
            $securityCondition->getObjectType(),
            $securityCondition->getObjectId(),
            $system
        );

        return $this->getRolesObjectPermissionsByArray($permissions, $roles, $system);
    }

    /**
     * Returns the permissions for the given permission array and the given user.
     *
     * @param array $permissions Object permissions
     * @param RoleInterface[] $roles The role for which the security should be checked
     *
     * @return array
     */
    private function getRolesObjectPermissionsByArray($permissions, array $roles, $system)
    {
        if (empty($permissions)) {
            return null;
        }

        $userPermission = [];
        foreach ($roles as $role) {
            $roleId = $role->getId();
            if (!isset($permissions[$roleId])) {
                continue;
            }

            if ($role->getSystem() !== $system) {
                continue;
            }

            $userPermission = $this->cumulatePermissions($userPermission, $permissions[$roleId]);
        }

        return $userPermission;
    }

    /**
     * Returns the permissions for the given security context for the given user.
     *
     * @param string $securityContext
     * @param RoleInterface[] $roles The role for which the security should be checked
     * @param bool $checkPermissionType Flag to show if the permission type should also be checked. If set to false
     *                                  it will only check if the user has access to the context in the given locale
     *
     * @return array
     */
    private function getRolesSecurityContextPermissions(
        $securityContext,
        array $roles,
        $checkPermissionType,
        $system
    ) {
        if (empty($roles)) {
            return $this->maskConverter->convertPermissionsToArray(0);
        }

        $userPermissions = [];

        foreach ($roles as $role) {
            if ($role->getSystem() !== $system) {
                continue;
            }

            $userPermissions = $this->cumulatePermissions(
                $userPermissions,
                $this->getRoleSecurityContextPermissions(
                    $securityContext,
                    $role,
                    $checkPermissionType
                )
            );
        }

        return $userPermissions;
    }

    /**
     * Returns the permissions for the given security context for the given user role.
     *
     * @param string $securityContext
     * @param RoleInterface $role The role for which the security is checked
     * @param bool $checkPermissionType Flag to show if the permission type should also be checked
     *
     * @return array
     */
    private function getRoleSecurityContextPermissions(
        $securityContext,
        RoleInterface $role,
        $checkPermissionType
    ) {
        $userPermission = [];

        foreach ($role->getPermissions() as $permission) {
            $hasContext = $permission->getContext() == $securityContext;

            if (!$hasContext) {
                continue;
            }

            if ($checkPermissionType) {
                $userPermission = $this->maskConverter->convertPermissionsToArray($permission->getPermissions());
            } else {
                \array_walk($userPermission, function(&$permission) {
                    $permission = true;
                });
            }
        }

        return $userPermission;
    }

    private function getRolesForLocale(UserInterface $user, ?string $locale)
    {
        $roles = [];

        foreach ($user->getUserRoles() as $userRole) {
            if ($locale != null && !\in_array($locale, $userRole->getLocales())) {
                continue;
            }

            $roles[] = $userRole->getRole();
        }

        return $roles;
    }

    /**
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
     */
    private function cumulatePermissions(array $userPermission, array $permissions)
    {
        return $this->mapPermissions($userPermission, $permissions, function($permission1, $permission2) {
            return $permission1 || $permission2;
        });
    }

    /**
     * Merges all the values for the given permission arrays. Only returns true if all values are true.
     *
     * @param array $permissions The array of the additional permissions
     * @param array $userPermission The array of the currently changing permissions
     */
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
