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
use Sulu\Bundle\ContactBundle\Entity\Contact;
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
class UserController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:User';
    protected $roleEntityName = 'SuluSecurityBundle:Role';
    protected $contactEntityName = 'SuluContactBundle:Contact';

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
            $user->setContact($this->getContact($this->getRequest()->get('contact')['id']));
            $user->setUsername($this->getRequest()->get('username'));
            $user->setSalt($this->generateSalt());
            $user->setPassword($this->encodePassword($user, $this->getRequest()->get('password'), $user->getSalt()));
            $user->setLocale($this->getRequest()->get('locale'));

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

            $user->setContact($this->getContact($this->getRequest()->get('contact')['id']));
            $user->setUsername($this->getRequest()->get('username'));
            $user->setSalt($this->generateSalt());
            $user->setPassword($this->encodePassword($user, $this->getRequest()->get('password'), $user->getSalt()));
            $user->setLocale($this->getRequest()->get('locale'));

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
            $this->getDoctrine()->getManager()->remove($userRole);
        };

        $update = function ($userRole, $userRoleData) {
            return $this->updateUserRole($userRole, $userRoleData);
        };

        $add = function ($userRole) use ($user) {
            return $this->addUserRole($user, $userRole);
        };

        return $this->processPut($user->getUserRoles(), $userRoles, $delete, $update, $add);
    }

    /**
     * Adds a new UserRole to the given user
     * @param User $user
     * @param $userRoleData
     * @return bool
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     */
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

    /**
     * Updates an existing UserRole with the given data
     * @param UserRole $userRole
     * @param $userRoleData
     * @return bool
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     */
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

    /**
     * Checks if all the arguments are given, and throws an exception if one is missing
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\MissingArgumentException
     */
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
        if ($this->getRequest()->get('contact') == null) {
            throw new MissingArgumentException($this->entityName, 'contact');
        }
    }


    /***
     * Checks if the id is valid
     * @param $id
     * @return bool
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\RestException
     */
    private function isValidId($id)
    {
        return  (is_int((int) $id) && $id > 0);
    }


     /* Returns the contact with the given id
     * @param $id
     * @return Contact
     * @throws \Sulu\Bundle\CoreBundle\Controller\Exception\EntityNotFoundException
     */
    private function getContact($id)
    {
        $contact =  $this->getDoctrine()
            ->getRepository($this->contactEntityName)
            ->find($id);

        if (!$contact) {
            throw new EntityNotFoundException($this->contactEntityName, $id);
        }

        return $contact;
    }

    /**
     * Generates a random salt for the password
     * @return string
     */
    private function generateSalt()
    {
        return $this->get('sulu_security.salt_generator')->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given passwort, with he given salt and returns the result
     * @param $user
     * @param $password
     * @param $salt
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);

    }

    /**
     * Returns a user with a specific contact id or all users
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        $contactId = $this->getRequest()->get('contactId');

        if ($contactId != null) {

            $user = $this->getDoctrine()
                ->getRepository($this->entityName)
                ->findUserByContact($contactId);

            if ($user == null) {
                $view = $this->view(null, 404);
            } else {
                $view = $this->view($user, 200);
            }

        } else {

            $view = $this->responseList();
        }
        return $this->handleView($view);
    }
}
