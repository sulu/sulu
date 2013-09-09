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
use Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException;
use Sulu\Bundle\CoreBundle\Controller\Exception\RestException;
use Sulu\Bundle\CoreBundle\Controller\RestController;
use Sulu\Bundle\SecurityBundle\Entity\Role;

/**
 * Makes the roles accessible through a REST-API
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class RolesController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:Role';

    public function listAction()
    {
        $view = $this->responseList();

        return $this->handleView($view);
    }

    /**
     * Returns the role with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function($id) {
            return $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);
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
        $context = $this->getRequest()->get('context');

        if ($name != null && $system != null && $context != null) {
            $em = $this->getDoctrine()->getManager();

            $role = new Role();
            $role->setName($name);
            $role->setSystem($system);
            $role->setContext($context);
            $role->setPermission($this->getRequest()->get('permission', 0));

            $em->persist($role);
            $em->flush();

            $view = $this->view($role, 200);
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
            ->find($id);

        try {
            if (!$role) {
                throw new EntityNotFoundException($this->entityName, $id);
            } else {
                $em = $this->getDoctrine()->getManager();

                $name = $this->getRequest()->get('name');

                $role->setName($name);
                $role->setSystem($this->getRequest()->get('system'));
                $role->setContext($this->getRequest()->get('context'));
                $role->setPermission($this->getRequest()->get('permission', 0));

                $em->flush();
                $view = $this->view($role);
            }
        } catch(EntityNotFoundException $enfe) {
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
        $delete = function($id) {
            $role = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);

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
}