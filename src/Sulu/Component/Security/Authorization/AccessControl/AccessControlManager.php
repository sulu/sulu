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
     * Adds a new AccessControlProvider.
     *
     * @param AccessControlProviderInterface $accessControlProvider The AccessControlProvider to add
     */
    public function addAccessControlProvider(AccessControlProviderInterface $accessControlProvider)
    {
        $this->accessControlProviders[] = $accessControlProvider;
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
