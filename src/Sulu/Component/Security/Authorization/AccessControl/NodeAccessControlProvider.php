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

use ReflectionClass;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class NodeAccessControlProvider implements AccessControlProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var array
     */
    private $permissions;

    public function __construct(DocumentManagerInterface $documentManager, array $permissions)
    {
        $this->documentManager = $documentManager;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function setPermissions($type, $identifier, $permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $roleName => $rolePermissions) {
            $allowedPermissions[$roleName] = $this->getAllowedPermissions($rolePermissions);
        }

        $document = $this->documentManager->find($identifier);
        $document->setPermissions($allowedPermissions);

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissions($type, $identifier)
    {
        $document = $this->documentManager->find($identifier);
        $allowedPermissions = $document->getPermissions();

        $permissions = [];
        foreach ($allowedPermissions as $roleName => $rolePermissions) {
            $permissions[$roleName] = [];
            foreach($this->permissions as $permission => $value) {
                $permissions[$roleName][$permission] = in_array($permission, $rolePermissions);
            }
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        $class = new ReflectionClass($type);

        return $class->implementsInterface(WebspaceBehavior::class);
    }

    /**
     * Extracts the keys of the allowed permissions into an own array.
     *
     * @param $permissions
     *
     * @return array
     */
    private function getAllowedPermissions($permissions)
    {
        $allowedPermissions = [];
        foreach ($permissions as $permission => $allowed) {
            if ($allowed) {
                $allowedPermissions[] = $permission;
            }
        }

        return $allowedPermissions;
    }
}
