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
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RestController;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use FOS\RestBundle\Controller\Annotations\Get;

/**
 * Makes the users accessible through a rest api
 * @package Sulu\Bundle\SecurityBundle\Controller
 */
class UserController extends RestController implements ClassResourceInterface
{
    protected $entityName = 'SuluSecurityBundle:User';

    const ENTITY_NAME_ROLE = 'SuluSecurityBundle:Role';
    const ENTITY_NAME_GROUP = 'SuluSecurityBundle:Group';
    const ENTITY_NAME_CONTACT = 'SuluContactBundle:Contact';

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
                ->findUserById($id);
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
            $userGroups = $this->getRequest()->get('userGroups');

            $this->checkArguments();

            $em = $this->getDoctrine()->getManager();

            $user = new User();
            $user->setContact($this->getContact($this->getRequest()->get('contact')['id']));
            $user->setUsername($this->getRequest()->get('username'));
            $user->setSalt($this->generateSalt());

            if ($this->isValidPassword($this->getRequest()->get('password'))) {
                $user->setPassword(
                    $this->encodePassword($user, $this->getRequest()->get('password'), $user->getSalt())
                );
            } else {
                throw new InvalidArgumentException($this->entityName, 'password');
            }

            $user->setLocale($this->getRequest()->get('locale'));

            $em->persist($user);
            $em->flush();

            if (!empty($userRoles)) {
                foreach ($userRoles as $userRole) {
                    $this->addUserRole($user, $userRole);
                }
            }

            if (!empty($userGroups)) {
                foreach ($userGroups as $userGroup) {
                    $this->addUserGroup($user, $userGroup);
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
     * Checks if the given password is a valid one
     * @param string $password The password to check
     * @return bool True if the password is valid, otherwise false
     */
    private function isValidPassword($password)
    {
        return !empty($password);
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
            ->findUserById($id);

        try {
            if (!$user) {
                throw new EntityNotFoundException($this->entityName, $id);
            }

            $this->checkArguments();

            $em = $this->getDoctrine()->getManager();

            $user->setContact($this->getContact($this->getRequest()->get('contact')['id']));
            $user->setUsername($this->getRequest()->get('username'));

            if ($this->getRequest()->get('password') != '') {
                $user->setSalt($this->generateSalt());
                $user->setPassword(
                    $this->encodePassword($user, $this->getRequest()->get('password'), $user->getSalt())
                );
            }

            $user->setLocale($this->getRequest()->get('locale'));

            if (!$this->processUserRoles($user) || !$this->processUserGroups($user)) {
                throw new RestException('Could not update dependencies!');
            }

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Takes a key, value pair and stores it as settings for the user
     * @param Number $userId the id of the user
     * @param String $key the settings key
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSettingsAction($id, $key)
    {
        $value = $this->getRequest()->get('value');

        try {
            if ($key === null || $value === null) {
                throw new InvalidArgumentException($this->entityName, 'key and value');
            }

            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            if ($user->getId() != $id) {
                throw new InvalidArgumentException($this->entityName, 'id');
            }

            // encode before persist
            $data = json_encode($value);

            // get setting
            /** @var UserSetting $setting */
            $setting = $this->getDoctrine()
                ->getRepository('SuluSecurityBundle:UserSetting')
                ->findOneBy(array('user' => $user, 'key' => $key));

            // or create new one
            if (!$setting) {
                $setting = new UserSetting();
                $setting->setKey($key);
                $setting->setUser($user);
                $em->persist($setting);
            }
            // persist setting
            $setting->setValue($data);
            $em->flush($setting);

            //create view
            $view = $this->view($setting, 200);
        } catch (InvalidArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Returns the settings for a key for the current user
     * @param String $key The settings key
     * @return \Symfony\Component\HttpFoundation\Response
     * @Get("users/settings/{key}")
     */
    public function getSettingsAction($key)
    {
        $user = $this->getUser();

        $setting = $this->getDoctrine()
            ->getRepository('SuluSecurityBundle:UserSetting')
            ->findOneBy(array('user' => $user, 'key' => $key));

        $view = $this->view($setting, 200);

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
                ->findUserById($id);

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
     * Process all user roles from request
     * @param User $user The user on which is worked
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
     * Process all user groups from request
     * @param User $user The user on which is worked
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserGroups(User $user)
    {
        $userGroups = $this->getRequest()->get('userGroups');

        $delete = function ($userGroup) use ($user) {
            $user->removeUserGroup($userGroup);
            $this->getDoctrine()->getManager()->remove($userGroup);
        };

        $update = function ($userGroup, $userGroupData) {
            return $this->updateUserGroup($userGroup, $userGroupData);
        };

        $add = function ($userGroup) use ($user) {
            return $this->addUserGroup($user, $userGroup);
        };

        return $this->processPut($user->getUserGroups(), $userGroups, $delete, $update, $add);
    }

    /**
     * Adds a new UserRole to the given user
     * @param User $user
     * @param $userRoleData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserRole(User $user, $userRoleData)
    {
        $em = $this->getDoctrine()->getManager();

        $role = $this->getDoctrine()
            ->getRepository(self::ENTITY_NAME_ROLE)
            ->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException(self::ENTITY_NAME_ROLE, $userRoleData['role']['id']);
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
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function updateUserRole(UserRole $userRole, $userRoleData)
    {
        $role = $this->getDoctrine()
            ->getRepository(self::ENTITY_NAME_ROLE)
            ->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException(self::ENTITY_NAME_ROLE, $userRole['role']['id']);
        }

        $userRole->setRole($role);
        if (array_key_exists('locales', $userRoleData)) {
            $userRole->setLocale(json_encode($userRoleData['locales']));
        } else {
            $userRole->setLocale($userRoleData['locale']);
        }

        return true;
    }

    /**
     * Adds a new UserGroup to the given user
     * @param User $user
     * @param $userGroupData
     * @return bool
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserGroup(User $user, $userGroupData)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $this->getDoctrine()
            ->getRepository(self::ENTITY_NAME_GROUP)
            ->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException(self::ENTITY_NAME_GROUP, $userGroupData['group']['id']);
        }

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setLocale(json_encode($userGroupData['locales']));
        $em->persist($userGroup);

        $user->addUserGroup($userGroup);

        return true;
    }

    /**
     * Updates an existing UserGroup with the given data
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroup
     * @param $userGroupData
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     * @return bool
     */
    private function updateUserGroup(UserGroup $userGroup, $userGroupData)
    {
        $group = $this->getDoctrine()
            ->getRepository(self::ENTITY_NAME_GROUP)
            ->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException(self::ENTITY_NAME_GROUP, $userGroup['group']['id']);
        }

        $userGroup->setGroup($group);
        if (array_key_exists('locales', $userGroupData)) {
            $userGroup->setLocale(json_encode($userGroupData['locales']));
        } else {
            $userGroup->setLocale($userGroupData['locale']);
        }

        return true;
    }

    /**
     * Checks if all the arguments are given, and throws an exception if one is missing
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     */
    private function checkArguments()
    {
        if ($this->getRequest()->get('username') == null) {
            throw new MissingArgumentException($this->entityName, 'username');
        }
        if ($this->getRequest()->get('password') === null) {
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
     * @throws \Sulu\Component\Rest\Exception\RestException
     */
    private function isValidId($id)
    {
        return (is_int((int)$id) && $id > 0);
    }


    /**
     * Returns the contact with the given id
     * @param $id
     * @return Contact
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function getContact($id)
    {
        $contact = $this->getDoctrine()
            ->getRepository(self::ENTITY_NAME_CONTACT)
            ->findById($id);

        if (!$contact) {
            throw new EntityNotFoundException(self::ENTITY_NAME_CONTACT, $id);
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
     * optional parameter 'flat' calls listAction
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction()
    {
        if ($this->getRequest()->get('flat') == 'true') {
            // flat structure
            $view = $this->responseList();
        } else {
            $contactId = $this->getRequest()->get('contactId');

            if ($contactId != null) {
                $user = $this->getDoctrine()->getRepository($this->entityName)->findUserByContact($contactId);
                if ($user == null) {
                    $view = $this->view(null, 404);
                } else {
                    $view = $this->view($user, 200);
                }
            } else {
                $entities = $this->getDoctrine()->getRepository($this->entityName)->findAll();
                $view = $this->view($this->createHalResponse($entities), 200);
            }
        }
        return $this->handleView($view);
    }

}
