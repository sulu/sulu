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

class AccessControlManager implements AccessControlManagerInterface
{
    /**
     * @var AccessControlProviderInterface[]
     */
    protected $accessControlProviders = [];

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $securityIdentity, $permissions)
    {
        // TODO: Implement setPermissions() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($type, $identifier)
    {
        // TODO: Implement getPermissions() method.
    }

    public function addAccessControlProvider(AccessControlProviderInterface $accessControlProvider)
    {
        $this->accessControlProviders[] = $accessControlProvider;
    }
}
