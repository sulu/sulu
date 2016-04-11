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

use Doctrine\Common\Persistence\ObjectManager;
use ReflectionClass;
use ReflectionException;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;

/**
 * This class handles permission information for doctrine entities.
 */
class DoctrineAccessControlProvider implements AccessControlProviderInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var AccessControlRepositoryInterface
     */
    private $accessControlRepository;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    /**
     * @param ObjectManager $objectManager
     * @param RoleRepositoryInterface $roleRepository
     * @param AccessControlRepositoryInterface $accessControlRepository
     * @param MaskConverterInterface $maskConverter
     */
    public function __construct(
        ObjectManager $objectManager,
        RoleRepositoryInterface $roleRepository,
        AccessControlRepositoryInterface $accessControlRepository,
        MaskConverterInterface $maskConverter
    ) {
        $this->objectManager = $objectManager;
        $this->roleRepository = $roleRepository;
        $this->accessControlRepository = $accessControlRepository;
        $this->maskConverter = $maskConverter;
    }

    /**
     * Sets the permissions for the object with the given class and id for the given security identity.
     *
     * @param string $type The name of the class to protect
     * @param string $identifier
     * @param $permissions
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        foreach ($permissions as $roleId => $rolePermissions) {
            $accessControl = $this->accessControlRepository->findByTypeAndIdAndRole($type, $identifier, $roleId);

            if ($accessControl) {
                $accessControl->setPermissions($this->maskConverter->convertPermissionsToNumber($rolePermissions));
            } else {
                $role = $this->roleRepository->findRoleById($roleId);

                $accessControl = new AccessControl();
                $accessControl->setPermissions($this->maskConverter->convertPermissionsToNumber($rolePermissions));
                $accessControl->setRole($role);
                $accessControl->setEntityId($identifier);
                $accessControl->setEntityClass($type);
                $this->objectManager->persist($accessControl);
            }
        }

        $this->objectManager->flush();
    }

    /**
     * Returns the permissions for all security identities.
     *
     * @param string $type The type of the protected object
     * @param string $identifier The identifier of the protected object
     *
     * @return array
     */
    public function getPermissions($type, $identifier)
    {
        $accessControls = $this->accessControlRepository->findByTypeAndId($type, $identifier);

        $permissions = [];
        foreach ($accessControls as $accessControl) {
            $permissions[$accessControl->getRole()->getId()] = $this->maskConverter->convertPermissionsToArray(
                $accessControl->getPermissions()
            );
        }

        return $permissions;
    }

    /**
     * Returns whether this provider supports the given type.
     *
     * @param string $type The name of the class protect
     *
     * @return bool
     */
    public function supports($type)
    {
        try {
            $class = new ReflectionClass($type);
        } catch (ReflectionException $e) {
            // in case the class does not exist there is no support
            return false;
        }

        return $class->implementsInterface(SecuredEntityInterface::class);
    }
}
