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
use Sulu\Bundle\SecurityBundle\Entity\User;

/**
 * Makes the users accessible through a rest api
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class UsersController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:User';

    /**
     * Lists all the users in the system
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction()
    {
        $view = $this->responseList();
        return $this->handleView($view);
    }

    /**
     * Returns the user with the given id
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
     * Creates a new user in the system
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction()
    {
        $username = $this->getRequest()->get('username');
        $password = $this->getRequest()->get('password');
        $locale = $this->getRequest()->get('locale');

        if ($username != null && $password != null && $locale != null) {
            $em = $this->getDoctrine()->getManager();

            $user = new User();
            $user->setUsername($username);
            $user->setPassword($password);
            $user->setLocale($locale);

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } else {
            $view = $this->view(null, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Updates the given user with the given data
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction($id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()
            ->getRepository($this->entityName)
            ->find($id);

        try {
            if (!$user) {
                throw new EntityNotFoundException($this->entityName, $id);
            }
            $em = $this->getDoctrine()->getManager();

            $user->setUsername($this->getRequest()->get('username'));
            $user->setPassword($this->getRequest()->get('password'));
            $user->setLocale($this->getRequest()->get('locale'));

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re, 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id) {
        $delete = function ($id) {
            $user = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->find($id);

            if (!$user) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }
}