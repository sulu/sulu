<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

/**
 * An implementation of Sulu's AccessControlManagerInterface, which is using the ACL component of Symfony.
 * Responsible for setting the permissions on a specific object.
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

    public function __construct(MutableAclProviderInterface $aclProvider, MaskConverterInterface $maskConverter)
    {
        $this->aclProvider = $aclProvider;
        $this->maskConverter = $maskConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $securityIdentity, $permissions)
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
            return array();
        }

        $permissions = array();

        foreach ($acl->getObjectAces() as $ace) {
            /** @var EntryInterface $ace */
            $permissions[$ace->getSecurityIdentity()->getRole()] =
                $this->maskConverter->convertPermissionsToArray($ace->getMask());
        }

        return $permissions;
    }
}
