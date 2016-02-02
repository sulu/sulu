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

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

/**
 * An implementation of Sulu's AccessControlManagerInterface, which is using the ACL component of Symfony.
 * Responsible for setting the permissions on a specific object.
 *
 * @deprecated will be removed with 1.2
 */
class SymfonyAccessControlManager implements AccessControlManagerInterface
{
    /**
     * @var MutableAclProviderInterface
     */
    private $aclProvider;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param MutableAclProviderInterface $aclProvider
     * @param MaskConverterInterface      $maskConverter
     * @param EventDispatcherInterface    $eventDispatcher
     */
    public function __construct(
        MutableAclProviderInterface $aclProvider,
        MaskConverterInterface $maskConverter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->aclProvider = $aclProvider;
        $this->maskConverter = $maskConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        foreach ($permissions as $securityIdentity => $permission) {
            $this->setPermission($type, $identifier, $securityIdentity, $permission);
        }
    }

    /**
     * Sets the permission for a single security identity.
     *
     * @param string $type The type of the object to protect
     * @param string $identifier The identifier of the object to protect
     * @param string $securityIdentity The security identity for which the permissions are set
     * @param array $permissions The permissions to set
     */
    private function setPermission($type, $identifier, $securityIdentity, $permissions)
    {
        $oid = new ObjectIdentity($identifier, $type);
        $sid = new RoleSecurityIdentity($securityIdentity);

        try {
            $acl = $this->aclProvider->findAcl($oid);
        } catch (AclNotFoundException $exc) {
            $acl = $this->aclProvider->createAcl($oid);
        }

        $updated = false;
        foreach ($acl->getObjectAces() as $id => $ace) {
            /** @var EntryInterface $ace */
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($id, $this->maskConverter->convertPermissionsToNumber($permissions));
                $updated = true;
            }
        }

        if (!$updated) {
            $acl->insertObjectAce(
                $sid,
                $this->maskConverter->convertPermissionsToNumber($permissions),
                0,
                true,
                'any'
            );
        }

        $this->aclProvider->updateAcl($acl);
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($type, $identifier)
    {
        $oid = new ObjectIdentity($identifier, $type);

        try {
            $acl = $this->aclProvider->findAcl($oid);
        } catch (AclNotFoundException $exc) {
            return [];
        }

        $permissions = [];

        foreach ($acl->getObjectAces() as $ace) {
            /* @var EntryInterface $ace */
            $permissions[$ace->getSecurityIdentity()->getRole()] =
                $this->maskConverter->convertPermissionsToArray($ace->getMask());
        }

        return $permissions;
    }

    /**
     * Returns the permissions regarding an object and its security context for a given user.
     *
     * @param SecurityCondition $securityCondition The condition to check
     * @param UserInterface $user The user for which the security is returned
     *
     * @return array
     */
    public function getUserPermissions(SecurityCondition $securityCondition, UserInterface $user)
    {
        // This class only exists for BC reasons, so new methods in the interface won't be implemented here
    }

    /**
     * Returns the permissions regarding an array of role permissions and its security context for a given user.
     *
     * @param string $locale
     * @param string $securityContext
     * @param $objectPermissionsByRole
     * @param UserInterface $user The user for which the security is returned
     *
     * @return array
     */
    public function getUserPermissionByArray($locale, $securityContext, $objectPermissionsByRole, UserInterface $user)
    {
        // This class only exists for BC reasons, so new methods in the interface won't be implemented here
    }
}
