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
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;

/**
 * Makes the groups accessible through a REST-API
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class GroupController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:Group';

    const ENTITY_NAME_ROLE = 'SuluSecurityBundle:Role';

    /**
     * returns all groups
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        if ($this->getRequest()->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList();
        } else {
            $groups = $this->getDoctrine()->getRepository($this->entityName)->findAllGroups();
            $view = $this->view($this->createHalResponse($groups), 200);
        }

        return $this->handleView($view);
    }

    /**
     * Returns the group with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            /** @var Group $group */
            $group = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findGroupById($id);

            return $group;
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new group with the given data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $name = $this->getRequest()->get('name');

        if ($name != null) {
            $em = $this->getDoctrine()->getManager();

            $group = new Group();
            $group->setName($name);

            $this->setParent($group);

            $group->setCreated(new \DateTime());
            $group->setChanged(new \DateTime());

            $roles = $this->getRequest()->get('roles');
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
     * Updates the group with the given id and the data given by the request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id)
    {
        /** @var Group $group */
        $group = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->findGroupById($id);

        try {
            if (!$group) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $this->getRequest()->get('name');

                $group->setName($name);

                $this->setParent($group);

                $group->setChanged(new \DateTime());

                if (!$this->processRoles($group)) {
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
     * Process all roles from request
     * @param Group $group The contact on which is worked
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processRoles(Group $group)
    {
        $roles = $this->getRequest()->get('roles');

        $delete = function ($role) use ($group) {
            $this->getDoctrine()->getManager()->remove($role);
        };

        $update = function ($role, $roleData) {
            return $this->updateRole($role, $roleData);
        };

        $add = function ($role) use ($group) {
            return $this->addRole($group, $role);
        };

        return $this->processPut($group->getRoles(), $roles, $delete, $update, $add);
    }

    /**
     * Deletes the group with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $group = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findGroupById($id);

            if (!$group) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($group);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Adds the given role to the group
     * @param Group $group
     * @param array $roleData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addRole(Group $group, $roleData)
    {
        $em = $this->getDoctrine()->getManager();

        if (isset($roleData['id'])) {
            $role = $em->getRepository(self::ENTITY_NAME_ROLE)->findRoleById($roleData['id']);

            if (!$role) {
                throw new EntityNotFoundException(self::ENTITY_NAME_ROLE, $roleData['id']);
            }

            $group->addRole($role);
        }

        return true;
    }

    /**
     * Updates an already existing role
     * @param Role $role
     * @param $roleData
     * @return bool
     */
    private function updateRole(Role $role, $roleData)
    {
        // no action on update
        return true;
    }

    /**
     * @param $group
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    public function setParent($group)
    {
        $parentData = $this->getRequest()->get('parent');
        if ($parentData != null && isset($parentData['id'])) {
            $parent = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findGroupById($parentData['id']);

            if (!$parent) {
                throw new EntityNotFoundException($this->entityName, $parentData['id']);
            }
            $group->setParent($parent);
        }
    }
} 
