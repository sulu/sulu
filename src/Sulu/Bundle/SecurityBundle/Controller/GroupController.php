<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the groups accessible through a REST-API.
 */
class GroupController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluSecurityBundle:Group';

    protected static $entityKey = 'groups';

    // TODO: Create a Manager and move the field descriptors to the manager
    /**
     * @var array - Holds the field descriptors for the list response
     */
    protected $fieldDescriptors;

    const ENTITY_NAME_ROLE = 'SuluSecurityBundle:Role';

    // TODO: move the field descriptors to a manager

    public function __construct()
    {
        $this->fieldDescriptors = [];
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', static::$entityName);
        $this->fieldDescriptors['name'] = new DoctrineFieldDescriptor('name', 'name', static::$entityName);
        $this->fieldDescriptors['created'] = new DoctrineFieldDescriptor('created', 'created', static::$entityName);
        $this->fieldDescriptors['changed'] = new DoctrineFieldDescriptor('changed', 'changed', static::$entityName);
    }

    /**
     * returns all groups.
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
                'get_groups',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $list = new CollectionRepresentation(
                $this->getDoctrine()->getRepository(static::$entityName)->findAllGroups(),
                static::$entityKey
            );
        }
        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Returns the group with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            /** @var Group $group */
            $group = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findGroupById($id);

            return $group;
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new group with the given data.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        $name = $request->get('name');

        if ($name != null) {
            $em = $this->getDoctrine()->getManager();

            $group = new Group();
            $group->setName($name);

            $this->setParent($group);

            $roles = $request->get('roles');
            if (!empty($roles)) {
                foreach ($roles as $roleData) {
                    $this->addRole($group, $roleData);
                }
            }

            $em->persist($group);
            $em->flush();

            $view = $this->view($group, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the group with the given id and the data given by the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var Group $group */
        $group = $this->getDoctrine()
            ->getRepository(static::$entityName)
            ->findGroupById($id);

        try {
            if (!$group) {
                throw new EntityNotFoundException(static::$entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $request->get('name');

                $group->setName($name);

                $this->setParent($group);

                if (!$this->processRoles($group, $request->get('roles', []))) {
                    throw new RestException('Could not update dependencies!');
                }

                $em->flush();
                $view = $this->view($group, 200);
            }
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Process all roles from request.
     *
     * @param Group $group The contact on which is worked
     * @param array $roles The roles to process
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processRoles(Group $group, $roles)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        $get = function ($entity) {
            /* @var RoleInterface $entity */
            return $entity->getId();
        };

        $delete = function ($role) use ($group) {
            $this->getDoctrine()->getManager()->remove($role);
        };

        $update = function ($role, $roleData) {
            return $this->updateRole($role, $roleData);
        };

        $add = function ($role) use ($group) {
            return $this->addRole($group, $role);
        };

        return $restHelper->processSubEntities($group->getRoles(), $roles, $get, $add, $update, $delete);
    }

    /**
     * Deletes the group with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $group = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findGroupById($id);

            if (!$group) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($group);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Adds the given role to the group.
     *
     * @param Group $group
     * @param array $roleData
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addRole(Group $group, $roleData)
    {
        if (isset($roleData['id'])) {
            $role = $this->get('sulu.repository.role')->findRoleById($roleData['id']);

            if (!$role) {
                throw new EntityNotFoundException($this->get('sulu.repository.role')->getClassName(), $roleData['id']);
            }

            if (!$group->getRoles()->contains($role)) {
                $group->addRole($role);
            }
        }

        return true;
    }

    /**
     * Updates an already existing role.
     *
     * @param RoleInterface $role
     * @param $roleData
     *
     * @return bool
     */
    private function updateRole(RoleInterface $role, $roleData)
    {
        // no action on update
        return true;
    }

    /**
     * @param $group
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function setParent($group)
    {
        $parentData = $this->getRequest()->get('parent');
        if ($parentData != null && isset($parentData['id'])) {
            $parent = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findGroupById($parentData['id']);

            if (!$parent) {
                throw new EntityNotFoundException(static::$entityName, $parentData['id']);
            }
            $group->setParent($parent);
        }
    }

    /**
     * Returns the SecurityContext required for the controller.
     *
     * @return mixed
     */
    public function getSecurityContext()
    {
        return 'sulu.security.groups';
    }
}
