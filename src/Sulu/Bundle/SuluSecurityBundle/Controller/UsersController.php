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
use Sulu\Bundle\CoreBundle\Controller\Exception\MissingArgumentException;
use Sulu\Bundle\CoreBundle\Controller\Exception\RestException;
use Sulu\Bundle\CoreBundle\Controller\RestController;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Makes the users accessible through a rest api
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class UsersController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:User';
    protected $roleEntityName = 'SuluSecurityBundle:Role';

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
        $find = function ($id) {
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
        try {
            $userRoles = $this->getRequest()->get('userRoles');

            $this->checkArguments();

            $em = $this->getDoctrine()->getManager();

            $user = new User();
            $user->setUsername($this->getRequest()->get('username'));
            $user->setPassword($this->getRequest()->get('password'));
            $user->setLocale($this->getRequest()->get('locale'));
            $user->setSalt($this->getRequest()->get('salt'));

            $em->persist($user);
            $em->flush();

            if (!empty($userRoles)) {
                foreach ($userRoles as $userRole) {
                    $this->addUserRole($user, $userRole);
                }
            }

            $em->flush();

            $view = $this->view($user, 200);
        } catch (RestException $re) {
            if (isset($user)) {
                $em->remove($user);
            }
            $view = $this->view($re->toArray(), 400);
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

            $this->checkArguments();

            $em = $this->getDoctrine()->getManager();

            $user->setUsername($this->getRequest()->get('username'));
            $user->setPassword($this->getRequest()->get('password'));
            $user->setLocale($this->getRequest()->get('locale'));
            $user->setSalt($this->getRequest()->get('salt'));

            if (!$this->processUserRoles($user)) {
                throw new RestException("Could not update dependencies!");
            }

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $enfe) {
            $view = $this->view($enfe->toArray(), 404);
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
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

    /**
     * Process all emails from request
     * @param User $user The contact on which is worked
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserRoles(User $user)
    {
        $userRoles = $this->getRequest()->get('userRoles');

        $delete = function ($userRole) use ($user) {
            $user->removeUserRole($userRole);
        };

        $update = function ($userRole, $userRoleData) {
            return $this->updateUserRole($userRole, $userRoleData);
        };

        $add = function ($userRole) use ($user) {
            return $this->addUserRole($user, $userRole);
        };

        return $this->processPut($user->getUserRoles(), $userRoles, $delete, $update, $add);
    }

    protected function addUserRole(User $user, $userRoleData)
    {
        $em = $this->getDoctrine()->getManager();

        $role = $this->getDoctrine()
            ->getRepository($this->roleEntityName)
            ->find($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException($this->roleEntityName, $userRoleData['role']['id']);
        }

        $userRole = new UserRole();
        $userRole->setUser($user);
        $userRole->setRole($role);
        $userRole->setLocale(json_encode($userRoleData['locales']));
        $em->persist($userRole);

        $user->addUserRole($userRole);

        return true;
    }

    private function updateUserRole(UserRole $userRole, $userRoleData)
    {
        $role = $this->getDoctrine()
            ->getRepository($this->roleEntityName)
            ->find($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException($this->roleEntityName, $userRole['role']['id']);
        }

        $userRole->setRole($role);
        if(array_key_exists('locales', $userRoleData)){
            $userRole->setLocale(json_encode($userRoleData['locales']));
        } else {
            $userRole->setLocale($userRoleData['locale']);
        }

        return true;
    }

    private function checkArguments()
    {
        if ($this->getRequest()->get('username') == null) {
            throw new MissingArgumentException($this->entityName, 'username');
        }
        if ($this->getRequest()->get('password') == null) {
            throw new MissingArgumentException($this->entityName, 'password');
        }
        if ($this->getRequest()->get('locale') == null) {
            throw new MissingArgumentException($this->entityName, 'locale');
        }
        if ($this->getRequest()->get('salt') == null) {
            throw new MissingArgumentException($this->entityName, 'salt');
        }
    }

    /**
     * Returns roles for a specific user
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRolesAction($id)
    {
        try {
            if ($this->isValidId($id)) {

                $find = function ($id) {
                    return $this->getDoctrine()
                        ->getRepository($this->entityName)
                        ->findRolesOfUser($id);
                };

                $view = $this->responseGetById($id, $find);

            } else {
                throw new RestException("Invalid id - id must be an integer and greater than 0!");
            }
        } catch (RestException $re) {
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);

    }

    /***
     * Checks if the id is valid
     * @param $id
     * @return bool
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\RestException
     */
    private function isValidId($id)
    {
        $tmp = (int) $id;
        if (is_int($tmp) && $tmp > 0) {
            return true;
        }
        return false;
    }
}
