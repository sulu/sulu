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

use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\InvalidArgumentException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Makes the users accessible through a rest api.
 */
class UserController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluSecurityBundle:User';

    protected static $entityKey = 'users';

    const ENTITY_NAME_ROLE = 'SuluSecurityBundle:Role';
    const ENTITY_NAME_GROUP = 'SuluSecurityBundle:Group';
    const ENTITY_NAME_CONTACT = 'SuluContactBundle:Contact';
    const ENTITY_NAME_USER_SETTING = 'SuluSecurityBundle:UserSetting';

    /**
     * Contains the field descriptors used by the list response.
     *
     * @var DoctrineFieldDescriptor[]
     */
    protected $fieldDescriptors;

    // TODO: move field descriptors to a manager
    public function __construct()
    {
        $this->fieldDescriptors = array();
        $this->fieldDescriptors['id'] = new DoctrineFieldDescriptor('id', 'id', static::$entityName);
        $this->fieldDescriptors['username'] = new DoctrineFieldDescriptor('username', 'username', static::$entityName);
        $this->fieldDescriptors['email'] = new DoctrineFieldDescriptor('email', 'email', static::$entityName);
        $this->fieldDescriptors['password'] = new DoctrineFieldDescriptor('password', 'password', static::$entityName);
        $this->fieldDescriptors['locale'] = new DoctrineFieldDescriptor('locale', 'locale', static::$entityName);
        $this->fieldDescriptors['salt'] = new DoctrineFieldDescriptor('salt', 'salt', static::$entityName);
        $this->fieldDescriptors['apiKey'] = new DoctrineFieldDescriptor('apiKey', 'apiKey', static::$entityName);
    }

    /**
     * Returns the user with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction($id)
    {
        $find = function ($id) {
            return $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findUserById($id);
        };

        $view = $this->responseGetById($id, $find);

        return $this->handleView($view);
    }

    /**
     * Creates a new user in the system.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $userRoles = $request->get('userRoles');
            $userGroups = $request->get('userGroups');
            $email = $request->get('email');
            $contact = $request->get('contact');

            $this->checkArguments($request);

            if (!$this->isUsernameUnique($request->get('username'))) {
                throw new UsernameNotUniqueException($request->get('username'));
            }

            $em = $this->getDoctrine()->getManager();

            $user = new User();
            $user->setContact($this->getContact($contact['id']));
            $user->setUsername($request->get('username'));
            $user->setSalt($this->generateSalt());

            // if no email passed try to use the contact's first email
            if ($email === null &&
                array_key_exists('emails', $contact) && count($contact['emails']) > 0 &&
                $this->isEmailUnique($contact['emails'][0]['email'])
            ) {
                $email = $contact['emails'][0]['email'];
            }

            if ($this->isValidPassword($request->get('password'))) {
                $user->setPassword(
                    $this->encodePassword($user, $request->get('password'), $user->getSalt())
                );
            } else {
                throw new MissingPasswordException();
            }

            if ($email !== null) {
                if (!$this->isEmailUnique($email)) {
                    throw new EmailNotUniqueException($email);
                }
                $user->setEmail($email);
            }

            $user->setLocale($request->get('locale'));

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
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (MissingPasswordException $exc) {
            $view = $this->view($exc->toArray(), 400);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $re) {
            if (isset($user)) {
                $em->remove($user);
            }
            $view = $this->view($re->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @Post("/users/{id}")
     *
     * @param $id
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postEnableUserAction($id, Request $request)
    {
        $action = $request->get('action');
        try {
            switch ($action) {
                case 'enable':
                    // call repository method
                    $user = $this->enableUser($id);
                    break;
                default:
                    throw new RestException('Unrecognized action: ' . $action);
            }

            // prepare view
            $view = $this->view($user, 200);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * @param $id
     *
     * @return User
     */
    private function enableUser($id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()
            ->getRepository(static::$entityName)
            ->findUserById($id);

        $em = $this->getDoctrine()->getManager();

        $user->setEnabled(true);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /**
     * Checks if the given password is a valid one.
     *
     * @param string $password The password to check
     *
     * @return bool True if the password is valid, otherwise false
     */
    private function isValidPassword($password)
    {
        return !empty($password);
    }

    /**
     * Updates the given user with the given data.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()
            ->getRepository(static::$entityName)
            ->findUserById($id);

        try {
            if (!$user) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            // check if username is already in database and the current user is not the user with this username
            if ($user->getUsername() != $request->get('username') &&
                !$this->isUsernameUnique($request->get('username'))
            ) {
                throw new UsernameNotUniqueException($request->get('username'));
            }

            $this->checkArguments($request);

            $em = $this->getDoctrine()->getManager();

            $user->setContact($this->getContact($request->get('contact')['id']));
            $user->setUsername($request->get('username'));

            if ($request->get('email') !== null) {
                if ($request->get('email') !== $user->getEmail() &&
                    !$this->isEmailUnique($request->get('email'))
                ) {
                    throw new EmailNotUniqueException($request->get('email'));
                }
                $user->setEmail($request->get('email'));
            } else {
                $user->setEmail(null);
            }

            if ($request->get('password') != '') {
                $user->setSalt($this->generateSalt());
                $user->setPassword(
                    $this->encodePassword($user, $request->get('password'), $user->getSalt())
                );
            }

            $user->setLocale($request->get('locale'));

            if (
                !$this->processUserRoles($user, $request->get('userRoles', array())) ||
                !$this->processUserGroups($user, $request->get('userGroups', array()))
            ) {
                throw new RestException('Could not update dependencies!');
            }

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Checks if a username is unique
     * Null and empty will always return false.
     *
     * @param $username
     *
     * @return bool
     */
    private function isUsernameUnique($username)
    {
        if ($username) {
            try {
                $this->getDoctrine()
                    ->getRepository(static::$entityName)
                    ->findUserByUsername($username);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an email-adress is unique
     * Null and empty will always return false.
     *
     * @param $email
     *
     * @return bool
     */
    private function isEmailUnique($email)
    {
        if ($email) {
            try {
                $this->getDoctrine()
                    ->getRepository(static::$entityName)
                    ->findUserByEmail($email);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * Partly updates a user entity for a given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        /** @var User $user */
        $user = $this->getDoctrine()
            ->getRepository(static::$entityName)
            ->findUserById($id);

        try {
            if (!$user) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            // check if username is already in database and the current user is not the user with this username
            if ($request->get('username') &&
                $user->getUsername() != $request->get('username') &&
                !$this->isUsernameUnique($request->get('username'))
            ) {
                throw new UsernameNotUniqueException($request->get('username'));
            }

            $username = $request->get('username');
            $password = $request->get('password');
            $email = $request->get('email');
            $contact = $request->get('contact');
            $locale = $request->get('locale');
            $userRoles = $request->get('userRoles');
            $userGroups = $request->get('userGroups');

            $em = $this->getDoctrine()->getManager();

            if ($username !== null) {
                $user->setUsername($username);
            }
            if ($password !== null) {
                $user->setSalt($this->generateSalt());
                $user->setPassword(
                    $this->encodePassword($user, $password, $user->getSalt())
                );
            }
            if ($email !== null) {
                if ($email !== $user->getEmail() && !$this->isEmailUnique($email)) {
                    throw new EmailNotUniqueException($email);
                }
                $user->setEmail($email);
            }
            if ($contact !== null) {
                $user->setContact($this->getContact($contact['id']));
            }
            if ($locale !== null) {
                $user->setLocale($locale);
            }

            if (
                ($userRoles !== null && !$this->processUserRoles($user, $userRoles)) ||
                ($userGroups !== null && !$this->processUserGroups($user, $userGroups))
            ) {
                throw new RestException('Could not update dependencies!');
            }

            $em->persist($user);
            $em->flush();

            $view = $this->view($user, 200);
        } catch (EntityNotFoundException $exc) {
            $view = $this->view($exc->toArray(), 404);
        } catch (UsernameNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (EmailNotUniqueException $exc) {
            $view = $this->view($exc->toArray(), 409);
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Takes a key, value pair and stores it as settings for the user.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param Number $id the id of the user
     * @param String $key the settings key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSettingsAction(Request $request, $id, $key)
    {
        $value = $request->get('value');

        try {
            if ($key === null || $value === null) {
                throw new InvalidArgumentException(static::$entityName, 'key and value');
            }

            $em = $this->getDoctrine()->getManager();
            $user = $this->getUser();

            if ($user->getId() != $id) {
                throw new InvalidArgumentException(static::$entityName, 'id');
            }

            // encode before persist
            $data = json_encode($value);

            // get setting
            /** @var UserSetting $setting */
            $setting = $this->getDoctrine()
                ->getRepository(static::ENTITY_NAME_USER_SETTING)
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
     * Returns the settings for a key for the current user.
     *
     * @param Number $id The id of the user
     * @param String $key The settings key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSettingsAction($id, $key)
    {
        try {
            $user = $this->getUser();

            if ($user->getId() != $id) {
                throw new InvalidArgumentException(static::$entityName, 'id');
            }

            $setting = $this->getDoctrine()
                ->getRepository(static::ENTITY_NAME_USER_SETTING)
                ->findOneBy(array('user' => $user, 'key' => $key));

            $view = $this->view($setting, 200);
        } catch (InvalidArgumentException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes the user with the given id.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction($id)
    {
        $delete = function ($id) {
            $user = $this->getDoctrine()
                ->getRepository(static::$entityName)
                ->findUserById($id);

            if (!$user) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            $em = $this->getDoctrine()->getManager();
            $em->remove($user);
            $em->flush();
        };

        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * Process all user roles from request.
     *
     * @param User $user The user on which is worked
     * @param array $userRoles
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserRoles(User $user, $userRoles)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        $get = function ($entity) {
            /** @var User $entity */

            return $entity->getId();
        };

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

        return $restHelper->processSubEntities($user->getUserRoles(), $userRoles, $get, $add, $update, $delete);
    }

    /**
     * Process all user groups from request.
     *
     * @param User $user The user on which is worked
     * @param $userGroups
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserGroups(User $user, $userGroups)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        $get = function ($entity) {
            /** @var User $entity */

            return $entity->getId();
        };

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

        return $restHelper->processSubEntities($user->getUserGroups(), $userGroups, $get, $add, $update, $delete);
    }

    /**
     * Adds a new UserRole to the given user.
     *
     * @param User $user
     * @param $userRoleData
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserRole(User $user, $userRoleData)
    {
        $em = $this->getDoctrine()->getManager();
        $alreadyContains = false;

        $role = $this->getDoctrine()
            ->getRepository(static::ENTITY_NAME_ROLE)
            ->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException(static::ENTITY_NAME_ROLE, $userRoleData['role']['id']);
        }

        if ($user->getUserRoles()) {
            foreach ($user->getUserRoles() as $containedRole) {
                if ($containedRole->getRole()->getId() === $role->getId()) {
                    $alreadyContains = true;
                }
            }
        }
        if ($alreadyContains === false) {
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $userRole->setLocale(json_encode($userRoleData['locales']));
            $em->persist($userRole);

            $user->addUserRole($userRole);
        }

        return true;
    }

    /**
     * Updates an existing UserRole with the given data.
     *
     * @param UserRole $userRole
     * @param $userRoleData
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function updateUserRole(UserRole $userRole, $userRoleData)
    {
        $role = $this->getDoctrine()
            ->getRepository(static::ENTITY_NAME_ROLE)
            ->findRoleById($userRoleData['role']['id']);

        if (!$role) {
            throw new EntityNotFoundException(static::ENTITY_NAME_ROLE, $userRole['role']['id']);
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
     * Adds a new UserGroup to the given user.
     *
     * @param User $user
     * @param $userGroupData
     *
     * @return bool
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function addUserGroup(User $user, $userGroupData)
    {
        $em = $this->getDoctrine()->getManager();

        $group = $this->getDoctrine()
            ->getRepository(static::ENTITY_NAME_GROUP)
            ->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException(static::ENTITY_NAME_GROUP, $userGroupData['group']['id']);
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
     * Updates an existing UserGroup with the given data.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroup
     * @param $userGroupData
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return bool
     */
    private function updateUserGroup(UserGroup $userGroup, $userGroupData)
    {
        $group = $this->getDoctrine()
            ->getRepository(static::ENTITY_NAME_GROUP)
            ->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException(static::ENTITY_NAME_GROUP, $userGroup['group']['id']);
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
     * Checks if all the arguments are given, and throws an exception if one is missing.
     *
     * @throws \Sulu\Component\Rest\Exception\MissingArgumentException
     */
    private function checkArguments(Request $request)
    {
        if ($request->get('username') == null) {
            throw new MissingArgumentException(static::$entityName, 'username');
        }
        if ($request->get('password') === null) {
            throw new MissingArgumentException(static::$entityName, 'password');
        }
        if ($request->get('locale') == null) {
            throw new MissingArgumentException(static::$entityName, 'locale');
        }
        if ($request->get('contact') == null) {
            throw new MissingArgumentException(static::$entityName, 'contact');
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
        return (is_int((int) $id) && $id > 0);
    }

    /**
     * Returns the contact with the given id.
     *
     * @param $id
     *
     * @return Contact
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     */
    private function getContact($id)
    {
        $contact = $this->getDoctrine()
            ->getRepository(static::ENTITY_NAME_CONTACT)
            ->findById($id);

        if (!$contact) {
            throw new EntityNotFoundException(static::ENTITY_NAME_CONTACT, $id);
        }

        return $contact;
    }

    /**
     * Generates a random salt for the password.
     *
     * @return string
     */
    private function generateSalt()
    {
        return $this->get('sulu_security.salt_generator')->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given passwort, with he given salt and returns the result.
     *
     * @param $user
     * @param $password
     * @param $salt
     *
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * Returns a user with a specific contact id or all users
     * optional parameter 'flat' calls listAction.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $view = null;
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
                'get_users',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $contactId = $request->get('contactId');

            if ($contactId != null) {
                $entities = array();
                $entities[] = $this->getDoctrine()->getRepository(static::$entityName)->findUserByContact($contactId);
                if (!$entities[0]) {
                    $view = $this->view(null, 204);
                }
            } else {
                $entities = $this->getDoctrine()->getRepository(static::$entityName)->findAll();
            }

            $list = new CollectionRepresentation($entities, static::$entityKey);
        }

        if (!$view) {
            $view = $this->view($list, 200);
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.security.users';
    }
}
