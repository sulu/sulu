<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\UserManager;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Sulu\Bundle\AdminBundle\UserManager\CurrentUserDataInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\RoleRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\ContactBundle\Entity\ContactRepository;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Persistence\RelationTrait;
use Sulu\Bundle\SecurityBundle\Entity\GroupRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Bundle\SecurityBundle\Security\Exception\UsernameNotUniqueException;
use Sulu\Bundle\SecurityBundle\Security\Exception\MissingPasswordException;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;

class UserManager implements UserManagerInterface
{
    use RelationTrait;

    protected static $entityName = 'SuluSecurityBundle:User';

    const ENTITY_NAME_ROLE = 'SuluSecurityBundle:Role';
    const ENTITY_NAME_GROUP = 'SuluSecurityBundle:Group';
    const ENTITY_NAME_CONTACT = 'SuluContactBundle:Contact';

    /**
     * @var CurrentUserDataInterface
     */
    private $currentUserData;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var GroupRepository
     */
    private $groupRepository;

    /**
     * @var ContactRepository
     */
    private $contactRepository;

    /**
     * @var SaltGenerator
     */
    private $saltGenerator;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    public function __construct(
        ObjectManager $em,
        EncoderFactory $encoderFactory,
        RoleRepository $roleRepository = null,
        GroupRepository $groupRepository = null,
        ContactRepository $contactRepository = null,
        SaltGenerator $saltGenerator = null,
        CurrentUserDataInterface $currentUserData = null,
        UserRepositoryInterface $userRepository = null
    ) {
        $this->em = $em;
        $this->encoderFactory = $encoderFactory;
        $this->roleRepository = $roleRepository;
        $this->groupRepository = $groupRepository;
        $this->contactRepository = $contactRepository;
        $this->saltGenerator = $saltGenerator;
        $this->currentUserData = $currentUserData;
        $this->userRepository = $userRepository;
    }

    /**
     * Returns user for given id.
     *
     * @param int $id userId
     *
     * @return User
     */
    public function getUserById($id)
    {
        /** @var ObjectRepository $repository */
        return $this->userRepository->find($id);
    }

    /**
     * Deletes a user with the given id
     *
     * @param int $id
     */
    public function delete($id)
    {
        $delete = function ($id) {
            $user = $this->userRepository->findUserById($id);
            if (!$user) {
                throw new EntityNotFoundException(static::$entityName, $id);
            }

            $this->em->remove($user);
            $this->em->flush();
        };

        return $delete;
    }

    /**
     * Return all users
     *
     * @return array
     */
    public function findAll()
    {
        return $this->userRepository->findAll();
    }

    /**
     * Creates a new user with the given data
     *
     * @param array $data
     *
     * @return User
     */
    public function save(
        $data,
        $locale,
        $id = null,
        $patch = false
    ) {
        $username = $this->getProperty($data, 'username');
        $contact = $this->getProperty($data, 'contact');
        $email = $this->getProperty($data, 'email');
        $password = $this->getProperty($data, 'password');
        $user = null;
        try {
            if ($id) {
                // PATCH & PUT
                $user = $this->userRepository->findUserById($id);
                if (!$user) {
                    throw new EntityNotFoundException(static::$entityName, $id);
                }
                $this->processEmail($user, $email);
            } else {
                // POST
                $user = new User();
                $this->processEmail($user, $email, $contact);
            }
            // check if username is already in database and the current user is not the user with this username
            if (!$patch || $username !== null) {
                if ($user->getUsername() != $username &&
                    !$this->isUsernameUnique($username)) {
                    throw new UsernameNotUniqueException($username);
                }
            }
            // check if password is valid
            if (!$patch || $password !== null) {
                $user->setSalt($this->generateSalt());
                if ($this->isValidPassword($password)) {
                    $user->setPassword(
                        $this->encodePassword($user, $password, $user->getSalt())
                    );
                } elseif (!$id) {
                    throw new MissingPasswordException();
                }
            }
            if (!$this->processUserRoles($user, $this->getProperty($data, 'userRoles', array())) ||
                !$this->processUserGroups($user, $this->getProperty($data, 'userGroups', array()))
            ) {
                throw new RestException('Could not update dependencies!');
            }

            if (!$patch || $username !== null) {
                $user->setUsername($username);
            }
            if (!$patch || $contact !== null) {
                $user->setContact($this->getContact($contact['id']));
            }
            if (!$patch || $locale !== null) {
                $user->setLocale($locale);
            }

        } catch (RestException $re) {
            if (isset($user)) {
                $this->em->remove($user);
            }
            throw $re;
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Returns username for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getUsernameByUserId($id)
    {
        return $this->getUserById($id)->getUsername();
    }

    /**
     * returns fullName for given id.
     *
     * @param int $id userId
     *
     * @return string
     */
    public function getFullNameByUserId($id)
    {
        return $this->getUserById($id)->getFullName();
    }

    /**
     * returns user data of current user.
     *
     * @return CurrentUserDataInterface
     */
    public function getCurrentUserData()
    {
        return $this->currentUserData;
    }

    /**
     * Checks if a username is unique
     * Null and empty will always return false
     *
     * @param string $username
     *
     * @return bool
     */
    public function isUsernameUnique($username)
    {
        if ($username) {
            try {
                $this->userRepository->findUserByUsername($username);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if an email-adress is unique
     * Null and empty will always return false
     *
     * @param string $email
     *
     * @return bool
     */
    public function isEmailUnique($email)
    {
        if ($email) {
            try {
                $this->userRepository->findUserByEmail($email);
            } catch (NoResultException $exc) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function enableUser($id)
    {
        /** @var User $user */
        $user = $this->userRepository->findUserById($id);
        $user->setEnabled(true);
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Checks if the given password is a valid one
     *
     * @param string $password The password to check
     *
     * @return bool True if the password is valid, otherwise false
     */
    public function isValidPassword($password)
    {
        return !empty($password);
    }

    /**
     * Process all user roles from request
     *
     * @param User $user The user on which is worked
     * @param array $userRoles
     *
     * @return bool True if the processing was successful, otherwise false
     */
    public function processUserRoles(User $user, $userRoles)
    {
        $get = function ($entity) {
            /** @var User $entity */

            return $entity->getId();
        };

        $delete = function ($userRole) use ($user) {
            $user->removeUserRole($userRole);
            $this->em->remove($userRole);
        };

        $update = function ($userRole, $userRoleData) {
            return $this->updateUserRole($userRole, $userRoleData);
        };

        $add = function ($userRole) use ($user) {
            return $this->addUserRole($user, $userRole);
        };
        $entities = $user->getUserRoles();
        $result = $this->processSubEntities(
            $entities,
            $userRoles,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Process all user groups from request
     *
     * @param User $user The user on which is worked
     * @param $userGroups
     *
     * @return bool True if the processing was successful, otherwise false
     */
    protected function processUserGroups(User $user, $userGroups)
    {
        $get = function ($entity) {
            /** @var User $entity */

            return $entity->getId();
        };

        $delete = function ($userGroup) use ($user) {
            $user->removeUserGroup($userGroup);
            $this->em->remove($userGroup);
        };

        $update = function ($userGroup, $userGroupData) {
            return $this->updateUserGroup($userGroup, $userGroupData);
        };

        $add = function ($userGroup) use ($user) {
            return $this->addUserGroup($user, $userGroup);
        };

        $entities = $user->getUserGroups();

        $result = $this->processSubEntities(
            $entities,
            $userGroups,
            $get,
            $add,
            $update,
            $delete
        );

        $this->resetIndexOfSubentites($entities);

        return $result;
    }

    /**
     * Updates an existing UserRole with the given data
     *
     * @param UserRole $userRole
     * @param $userRoleData
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return bool
     */
    private function updateUserRole(UserRole $userRole, $userRoleData)
    {
        $role = $this->roleRepository->findRoleById($userRoleData['role']['id']);

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
     * Adds a new UserRole to the given user
     *
     * @param User $user
     * @param $userRoleData
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return bool
     */
    private function addUserRole(User $user, $userRoleData)
    {
        $alreadyContains = false;

        $role = $this->roleRepository->findRoleById($userRoleData['role']['id']);

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
            $this->em->persist($userRole);

            $user->addUserRole($userRole);
        }

        return true;
    }

    /**
     * Adds a new UserGroup to the given user
     *
     * @param User $user
     * @param $userGroupData
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return bool
     */
    private function addUserGroup(User $user, $userGroupData)
    {
        $group = $this->groupRepository->findGroupById($userGroupData['group']['id']);

        if (!$group) {
            throw new EntityNotFoundException(static::ENTITY_NAME_GROUP, $userGroupData['group']['id']);
        }

        $userGroup = new UserGroup();
        $userGroup->setUser($user);
        $userGroup->setGroup($group);
        $userGroup->setLocale(json_encode($userGroupData['locales']));
        $this->em->persist($userGroup);

        $user->addUserGroup($userGroup);

        return true;
    }

    /**
     * Updates an existing UserGroup with the given data
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
        $group = $this->groupRepository->findGroupById($userGroupData['group']['id']);

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
     * Returns the contact with the given id
     *
     * @param int $id
     *
     * @throws \Sulu\Component\Rest\Exception\EntityNotFoundException
     *
     * @return Contact
     */
    private function getContact($id)
    {
        $contact = $this->contactRepository->findById($id);

        if (!$contact) {
            throw new EntityNotFoundException(static::ENTITY_NAME_CONTACT, $id);
        }

        return $contact;
    }

    /**
     * Generates a random salt for the password
     *
     * @return string
     */
    private function generateSalt()
    {
        return $this->saltGenerator->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given passwort, with he given salt and returns the result
     *
     * @param User $user
     * @param string $password
     * @param string $salt
     *
     * @return string
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

    /**
     * Return property for key or given default value
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return string|null
     */
    private function getProperty($data, $key, $default = null)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * Processes the email and adds it to the user
     *
     * @param User $user
     * @param string $email
     * @param string $method
     */
    private function processEmail($user, $email, $contact = null)
    {
        if ($contact) {
            // POST
            // if no email passed try to use the contact's first email
            if ($email === null &&
                array_key_exists('emails', $contact) && count($contact['emails']) > 0 &&
                $this->isEmailUnique($contact['emails'][0]['email'])
            ) {
                $email = $contact['emails'][0]['email'];
            }
            if ($email !== null) {
                if (!$this->isEmailUnique($email)) {
                    throw new EmailNotUniqueException($email);
                }
                $user->setEmail($email);
            }
        } else {
            if ($email !== null) {
                if ($email !== $user->getEmail() &&
                    !$this->isEmailUnique($email)
                ) {
                    throw new EmailNotUniqueException($email);
                }
                $user->setEmail($email);
            } else {
                $user->setEmail(null);
            }
        }
    }

    /**
     * this is just a hack to avoid relations that start with index != 0
     * otherwise deserialization process will parse relations as object instead of an array
     * reindex entities
     * @param mixed $entities
     */
    private function resetIndexOfSubentites($entities)
    {
        if (sizeof($entities) > 0 && method_exists($entities, 'getValues')) {
            $newEntities = $entities->getValues();
            $entities->clear();
            foreach ($newEntities as $value) {
                $entities->add($value);
            }
        }

        return $entities;
    }
}
