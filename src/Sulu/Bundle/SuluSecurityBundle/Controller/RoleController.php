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

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the roles accessible through a REST-API
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class RoleController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:Role';

    const ENTITY_NAME_PERMISSION = 'SuluSecurityBundle:Permission';

    protected $fieldsDefault = array('name');
    protected $fieldsExcluded = array();
    protected $fieldsHidden = array('changed','created');
    protected $fieldsRelations = array();
    protected $fieldsSortOrder = array(0=>'id',1=>'name');
    protected $fieldsTranslationKeys = array();
    protected $bundlePrefix = 'security.roles.';


    /**
     * returns all fields that can be used by list
     * @Get("roles/fields")
     * @return mixed
     */
    public function getFieldsAction() {
        return $this->responseFields();
    }

    /**
     * persists a setting
     * @Put("roles/fields")
     */
    public function putFieldsAction() {
        return $this->responsePersistSettings();
    }

    /**
     * returns all roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        if ($this->getRequest()->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList();
        } else {
            $roles = $this->getDoctrine()->getRepository($this->entityName)->findAllRoles();

            if ($roles != null) {
                $convertedRoles = [];
                foreach ($roles as $role) {
                    array_push($convertedRoles, $this->convertRole($role));
                }
                $view = $this->view($this->createHalResponse($convertedRoles), 200);

            } else {
                $view = $this->view(array(), 200);
            }
        }
        return $this->handleView($view);
    }


    /**
     * Returns the role with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            /** @var Role $role */
            $role = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findRoleById($id);

            return $this->convertRole($role);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new role with the given data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $name = $this->getRequest()->get('name');
        $system = $this->getRequest()->get('system');

        if ($name != null && $system != null) {
            $em = $this->getDoctrine()->getManager();

            $role = new Role();
            $role->setName($name);
            $role->setSystem($system);

            $role->setCreated(new \DateTime());
            $role->setChanged(new \DateTime());

            $permissions = $this->getRequest()->get('permissions');
            if (!empty($permissions)) {
                foreach ($permissions as $permissionData) {
                    $this->addPermission($role, $permissionData);
                }
            }

            $em->persist($role);
            $em->flush();

            $view = $this->view($this->convertRole($role), 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the role with the given id and the data given by the request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id)
    {
        /** @var Role $role */
        $role = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findRoleById($id);

        try {
            if (!$role) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $this->getRequest()->get('name');

                $role->setName($name);
                $role->setSystem($this->getRequest()->get('system'));

                $role->setChanged(new \DateTime());

                if (!$this->processPermissions($role)) {
                    throw new RestException("Could not update dependencies!");
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
     * Deletes the role with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $role = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findRoleById($id);

            if (!$role) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($role);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all permissions from request
     * @param Role $role The contact on which is worked
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processPermissions(Role $role)
    {
        $permissions = $this->getRequest()->get('permissions');

        $delete = function ($permission) use ($role) {
            $this->getDoctrine()->getManager()->remove($permission);
        };

        $update = function ($permission, $permissionData) {
            return $this->updatePermission($permission, $permissionData);
        };

        $add = function ($permission) use ($role) {
            return $this->addPermission($role, $permission);
        };

        return $this->processPut($role->getPermissions(), $permissions, $delete, $update, $add);
    }

    /**
     * Adds a permission to the given role
     * @param Role $role
     * @param $permissionData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     */
    protected function addPermission(Role $role, $permissionData)
    {
        $em = $this->getDoctrine()->getManager();

        if (isset($permissionData['id'])) {
            throw new EntityIdAlreadySetException(self::ENTITY_NAME_PERMISSION, $permissionData['id']);
        }

        $permission = new Permission();
        $permission->setContext($permissionData['context']);
        $permission->setPermissions(
            $this->get('sulu_security.mask_converter')
                ->convertPermissionsToNumber($permissionData['permissions'])
        );
        $permission->setRole($role);
        $em->persist($permission);
        $role->addPermission($permission);

        return true;
    }

    /**
     * Updates an already existing permission
     * @param Permission $permission
     * @param $permissionData
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
     * Converts a role object into an array for the rest service
     * @param Role $role
     * @return array
     */
    protected function convertRole(Role $role)
    {
        $roleData['_links'] = $role->getLinks();

        $roleData['id'] = $role->getId();
        $roleData['name'] = $role->getName();
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
                            ->convertPermissionsToArray($permission->getPermissions())
                );
            }
        }

        return $roleData;
    }
}
