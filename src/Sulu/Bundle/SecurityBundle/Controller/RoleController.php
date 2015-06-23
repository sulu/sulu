<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException as DoctrineUniqueConstraintViolationException;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Exception\UniqueConstraintViolationException as SuluUniqueConstraintViolationException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the roles accessible through a REST-API.
 */
class RoleController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluSecurityBundle:Role';

    protected static $entityKey = 'roles';

    const ENTITY_NAME_PERMISSION = 'SuluSecurityBundle:Permission';

    protected $fieldsDefault = array('name');
    protected $fieldsExcluded = array();
    protected $fieldsHidden = array('changed', 'created');
    protected $fieldsRelations = array();
    protected $fieldsSortOrder = array(0 => 'id', 1 => 'name');
    protected $fieldsTranslationKeys = array();
    protected $bundlePrefix = 'security.roles.';

    /**
     * @var Array - Holds the field descriptors for the list response
     * TODO: Create a Manager and move the field descriptors to the manager
     */
    protected $fieldDescriptors;

    /**
     * TODO: move the field descriptors to a manager.
     */
    public function __construct()
    {
        $this->fieldDescriptors = array();
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            static::$entityName,
            'public.id',
            array(),
            false, false, '', '50px'
        );
        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            static::$entityName,
            'public.name'
        );
        $this->fieldDescriptors['system'] = new DoctrineFieldDescriptor(
            'system',
            'system',
            static::$entityName,
            'security.roles.system'
        );
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            static::$entityName,
            'public.created',
            array(),
            false, false, 'date'
        );
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            static::$entityName,
            'public.changed',
            array(),
            true, false, 'date'
        );
    }

    /**
     * returns all fields that can be used by list.
     *
     * @Get("roles/fields")
     *
     * @return mixed
     */
    public function getFieldsAction()
    {
        // default contacts list
        return $this->handleView($this->view(array_values($this->fieldDescriptors), 200));
    }

    /**
     * persists a setting.
     *
     * @Put("roles/fields")
     */
    public function putFieldsAction()
    {
        return $this->responsePersistSettings();
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
        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(static::$entityName);

            $restHelper->initializeListBuilder($listBuilder, $this->fieldDescriptors);

            $list = new ListRepresentation(
                $listBuilder->execute(),
                static::$entityKey,
                'get_roles',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $roles = $this->getDoctrine()->getRepository(static::$entityName)->findAllRoles();
            $convertedRoles = [];
            if ($roles != null) {
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
        $find = function ($id) {
            /** @var Role $role */
            $role = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findRoleById($id);

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
            if ($name === null) {
                throw new InvalidArgumentException('Role', 'name');
            }
            if ($system === null) {
                throw new InvalidArgumentException('Role', 'name');
            }

            $em = $this->getDoctrine()->getManager();

            $role = new Role();
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
                $em->persist($role);
                $em->flush();

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
        /** @var Role $role */
        $role = $this->getDoctrine()
            ->getRepository(static::$entityName)
            ->findRoleById($id);

        try {
            if (!$role) {
                throw new EntityNotFoundException(static::$entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $request->get('name');

                $role->setName($name);
                $role->setSystem($request->get('system'));

                if (!$this->processPermissions($role, $request->get('permissions', array()))) {
                    throw new RestException('Could not update dependencies!');
                }

                $securityTypeData = $request->get('securityType');
                if ($this->checkSecurityTypeData($securityTypeData)) {
                    $this->setSecurityType($role, $securityTypeData);
                } else {
                    $role->setSecurityType(null);
                }

                $em->flush();
                $view = $this->view($this->convertRole($role), 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
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
        $delete = function ($id) {
            $role = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findRoleById($id);

            if (!$role) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($role);
            $em->flush();
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
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        $get = function ($entity) {
            /** @var Permission $entity */

            return $entity->getId();
        };

        $delete = function ($permission) use ($role) {
            $this->getDoctrine()->getManager()->remove($permission);
        };

        $update = function ($permission, $permissionData) {
            return $this->updatePermission($permission, $permissionData);
        };

        $add = function ($permission) use ($role) {
            return $this->addPermission($role, $permission);
        };

        return $restHelper->processSubEntities($role->getPermissions(), $permissions, $get, $add, $update, $delete);
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
        $em = $this->getDoctrine()->getManager();
        $alreadyContains = false;

        if (isset($permissionData['id'])) {
            $permission = $em->getRepository(static::ENTITY_NAME_PERMISSION)->find($permissionData['id']);
            if (!$permission) {
                throw new EntityNotFoundException(static::ENTITY_NAME_PERMISSION, $permissionData['id']);
            }
            // only add if not already contains
            $alreadyContains = $role->getPermissions()->contains($permission);
        } else {
            $permission = new Permission();
            $permission->setContext($permissionData['context']);
            $permission->setPermissions(
                $this->get('sulu_security.mask_converter')
                    ->convertPermissionsToNumber($permissionData['permissions'])
            );
        }
        if ($alreadyContains === false) {
            $permission->setRole($role);
            $em->persist($permission);
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
            $this->get('sulu_security.mask_converter')
                ->convertPermissionsToNumber($permissionData['permissions'])
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
        $roleData['permissions'] = array();

        $permissions = $role->getPermissions();
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                /** @var Permission $permission */
                $roleData['permissions'][] = array(
                    'id' => $permission->getId(),
                    'context' => $permission->getContext(),
                    'module' => $permission->getModule(),
                    'permissions' => $this->get('sulu_security.mask_converter')
                        ->convertPermissionsToArray($permission->getPermissions()),
                );
            }
        }

        $securityType = $role->getSecurityType();
        if ($securityType) {
            $roleData['securityType'] = array(
                'id' => $securityType->getId(),
                'name' => $securityType->getName(),
            );
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
        return $securityTypeData != null && $securityTypeData['id'] != null && $securityTypeData['id'] != '';
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
        $securityType = $this->getDoctrine()
            ->getRepository('SuluSecurityBundle:SecurityType')
            ->findSecurityTypeById($securityTypeData['id']);

        if (!$securityType) {
            throw new EntityNotFoundException('SuluSecurityBundle:SecurityType', $securityTypeData['id']);
        }
        $role->setSecurityType($securityType);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.security.roles';
    }
}
