<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException as DoctrineUniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Exception\RoleNameAlreadyExistsException;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Exception\UniqueConstraintViolationException as SuluUniqueConstraintViolationException;
use Sulu\Component\Rest\ListBuilder\CollectionRepresentation;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the roles accessible through a REST-API.
 */
class RoleController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityKey = 'roles';

    const ENTITY_NAME_PERMISSION = 'SuluSecurityBundle:Permission';

    protected $bundlePrefix = 'security.roles.';

    /**
     * @var array - Holds the field descriptors for the list response
     */
    protected $fieldDescriptors = [];

    /**
     * @var FieldDescriptorFactoryInterface
     */
    private $fieldDescriptorFactory;

    /**
     * @var RestHelperInterface
     */
    private $restHelper;

    /**
     * @var DoctrineListBuilderFactoryInterface
     */
    private $doctrineListBuilderFactory;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $roleClass;

    public function __construct(
        ViewHandlerInterface $viewHandler,
        FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        RestHelperInterface $restHelper,
        DoctrineListBuilderFactoryInterface $doctrineListBuilderFactory,
        MaskConverterInterface $maskConverter,
        RoleRepositoryInterface $roleRepository,
        EntityManagerInterface $entityManager,
        string $roleClass
    ) {
        parent::__construct($viewHandler);

        $this->fieldDescriptorFactory = $fieldDescriptorFactory;
        $this->restHelper = $restHelper;
        $this->doctrineListBuilderFactory = $doctrineListBuilderFactory;
        $this->maskConverter = $maskConverter;
        $this->roleRepository = $roleRepository;
        $this->entityManager = $entityManager;
        $this->roleClass = $roleClass;
    }

    protected function getFieldDescriptors()
    {
        if (empty($this->fieldDescriptors)) {
            $this->initFieldDescriptors();
        }

        return $this->fieldDescriptors;
    }

    private function initFieldDescriptors()
    {
        $this->fieldDescriptors = $this->fieldDescriptorFactory
             ->getFieldDescriptors('roles');
    }

    /**
     * returns all roles.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        if ('true' == $request->get('flat')) {
            $listBuilder = $this->doctrineListBuilderFactory->create($this->roleClass);

            $this->restHelper->initializeListBuilder($listBuilder, $this->getFieldDescriptors());

            $list = new ListRepresentation(
                $listBuilder->execute(),
                static::$entityKey,
                'sulu_security.get_roles',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $roles = $this->roleRepository->findAllRoles();
            $convertedRoles = [];
            if (null != $roles) {
                foreach ($roles as $role) {
                    array_push($convertedRoles, $this->convertRole($role));
                }
            }
            $list = new CollectionRepresentation($convertedRoles, static::$entityKey);
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns the role with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function($id) {
            /** @var RoleInterface $role */
            $role = $this->roleRepository->findRoleById($id);

            return $this->convertRole($role);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new role with the given data.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');
        $system = $request->get('system');

        try {
            if (null === $name) {
                throw new InvalidArgumentException('Role', 'name');
            }
            if (null === $system) {
                throw new InvalidArgumentException('Role', 'name');
            }

            /** @var RoleInterface $role */
            $role = $this->roleRepository->createNew();
            $role->setName($name);
            $role->setSystem($system);

            $permissions = $request->get('permissions');
            if (!empty($permissions)) {
                foreach ($permissions as $permissionData) {
                    $this->addPermission($role, $permissionData);
                }
            }

            $securityTypeData = $request->get('securityType');
            if ($this->checkSecurityTypeData($securityTypeData)) {
                $this->setSecurityType($role, $securityTypeData);
            }

            try {
                $this->entityManager->persist($role);
                $this->entityManager->flush();

                $view = $this->view($this->convertRole($role), 200);
            } catch (DoctrineUniqueConstraintViolationException $ex) {
                throw new SuluUniqueConstraintViolationException('name', 'SuluSecurityBudle:Role');
            }
        } catch (RestException $ex) {
            $view = $this->view($ex->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the role with the given id and the data given by the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var RoleInterface $role */
        $role = $this->roleRepository->findRoleById($id);
        $name = $request->get('name');

        try {
            if (!$role) {
                throw new EntityNotFoundException($this->roleRepository->getClassName(), $id);
            } else {
                $role->setName($name);
                $role->setSystem($request->get('system'));

                if (!$this->processPermissions($role, $request->get('permissions', []))) {
                    throw new RestException('Could not update dependencies!');
                }

                $securityTypeData = $request->get('securityType');
                if ($this->checkSecurityTypeData($securityTypeData)) {
                    $this->setSecurityType($role, $securityTypeData);
                } else {
                    $role->setSecurityType(null);
                }

                $this->entityManager->flush();
                $view = $this->view($this->convertRole($role), 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (DoctrineUniqueConstraintViolationException $e) {
            throw new RoleNameAlreadyExistsException($name);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the role with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function($id) {
            $role = $this->roleRepository->findRoleById($id);

            if (!$role) {
                throw new EntityNotFoundException($this->roleRepository->getClassName(), $id);
            }

            $this->entityManager->remove($role);
            $this->entityManager->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all permissions from request.
     *
     * @param RoleInterface $role The contact on which is worked
     * @param $permissions
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPermissions(RoleInterface $role, $permissions)
    {
        $get = function($entity) {
            /* @var Permission $entity */

            return $entity->getId();
        };

        $delete = function($permission) {
            $this->entityManager->remove($permission);
        };

        $update = function($permission, $permissionData) {
            return $this->updatePermission($permission, $permissionData);
        };

        $add = function($permission) use ($role) {
            return $this->addPermission($role, $permission);
        };

        return $this->restHelper->processSubEntities(
            $role->getPermissions(), $permissions, $get, $add, $update, $delete
        );
    }

    /**
     * Adds a permission to the given role.
     *
     * @param RoleInterface $role
     * @param $permissionData
     *
     * @return bool
     *
     * @throws EntityNotFoundException
     */
    protected function addPermission(RoleInterface $role, $permissionData)
    {
        $alreadyContains = false;

        if (isset($permissionData['id'])) {
            $permission = $this->entityManager->getRepository(static::ENTITY_NAME_PERMISSION)->find($permissionData['id']);
            if (!$permission) {
                throw new EntityNotFoundException(static::ENTITY_NAME_PERMISSION, $permissionData['id']);
            }
            // only add if not already contains
            $alreadyContains = $role->getPermissions()->contains($permission);
        } else {
            $permission = new Permission();
            $permission->setContext($permissionData['context']);
            $permission->setPermissions(
                $this->maskConverter->convertPermissionsToNumber($permissionData['permissions'])
            );
        }
        if (false === $alreadyContains) {
            $permission->setRole($role);
            $this->entityManager->persist($permission);
            $role->addPermission($permission);
        }

        return true;
    }

    /**
     * Updates an already existing permission.
     *
     * @param Permission $permission
     * @param $permissionData
     *
     * @return bool
     */
    private function updatePermission(Permission $permission, $permissionData)
    {
        $permission->setContext($permissionData['context']);

        $permission->setPermissions(
            $this->maskConverter->convertPermissionsToNumber($permissionData['permissions'])
        );

        return true;
    }

    /**
     * Converts a role object into an array for the rest service.
     *
     * @param RoleInterface $role
     *
     * @return array
     */
    protected function convertRole(RoleInterface $role)
    {
        $roleData['id'] = $role->getId();
        $roleData['name'] = $role->getName();
        $roleData['identifier'] = $role->getIdentifier();
        $roleData['system'] = $role->getSystem();
        $roleData['permissions'] = [];

        $permissions = $role->getPermissions();
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                /* @var Permission $permission */
                $roleData['permissions'][] = [
                    'id' => $permission->getId(),
                    'context' => $permission->getContext(),
                    'module' => $permission->getModule(),
                    'permissions' => $this->maskConverter->convertPermissionsToArray($permission->getPermissions()),
                ];
            }
        }

        $securityType = $role->getSecurityType();
        if ($securityType) {
            $roleData['securityType'] = [
                'id' => $securityType->getId(),
                'name' => $securityType->getName(),
            ];
        }

        return $roleData;
    }

    /**
     * Checks if the data of the security type is correct.
     *
     * @param $securityTypeData
     *
     * @return bool
     */
    private function checkSecurityTypeData($securityTypeData)
    {
        return null != $securityTypeData && null != $securityTypeData['id'] && '' != $securityTypeData['id'];
    }

    /**
     * Sets the securityType from the given data to the role.
     *
     * @param RoleInterface $role
     * @param $securityTypeData
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function setSecurityType($role, $securityTypeData)
    {
        $securityType = $this->entityManager
            ->getRepository('SuluSecurityBundle:SecurityType')
            ->findSecurityTypeById($securityTypeData['id']);

        if (!$securityType) {
            throw new EntityNotFoundException('SuluSecurityBundle:SecurityType', $securityTypeData['id']);
        }
        $role->setSecurityType($securityType);
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.security.roles';
    }
}
