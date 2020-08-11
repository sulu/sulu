<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\ContactBundle\Entity\ContactRepositoryInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * An UserProvider which returns always the same user for testing purposes.
 */
class TestUserProvider implements UserProviderInterface
{
    const TEST_USER_USERNAME = 'test';

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ContactRepositoryInterface
     */
    private $contactRepository;

    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var EncoderFactoryInterface
     */
    private $userPasswordEncoderFactory;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    public function __construct(
        EntityManager $entityManager,
        ContactRepositoryInterface $contactRepository,
        UserRepositoryInterface $userRepository,
        EncoderFactoryInterface $userPasswordEncoderFactory,
        UserProviderInterface $userProvider,
        RoleRepositoryInterface $roleRepository
    ) {
        $this->entityManager = $entityManager;
        $this->contactRepository = $contactRepository;
        $this->userRepository = $userRepository;
        $this->userPasswordEncoderFactory = $userPasswordEncoderFactory;
        $this->userProvider = $userProvider;
        $this->roleRepository = $roleRepository;
    }

    public function getUser(string $username = self::TEST_USER_USERNAME)
    {
        if ($this->user && self::TEST_USER_USERNAME === $username) {
            return $this->user;
        }

        $user = $this->userRepository->findOneByUsername($username);

        if (!$user) {
            $contact = $this->contactRepository->createNew();
            $contact->setFirstName('Max');
            $contact->setLastName('Mustermann');
            $this->entityManager->persist($contact);

            $user = $this->userRepository->createNew();
            $user->setUsername($username);
            $user->setContact($contact);
            $this->entityManager->persist($user);
        }

        $user->setSalt('');
        $encoder = $this->userPasswordEncoderFactory->getEncoder($user);
        $user->setPassword($encoder->encodePassword($username, $user->getSalt()));
        $user->setLocale('en');

        $this->entityManager->flush();

        if (self::TEST_USER_USERNAME === $username) {
            $this->user = $user;
        }

        return $user;
    }

    public function getRole(
        string $name,
        string $system = Admin::SULU_ADMIN_SECURITY_SYSTEM,
        bool $anonymous = false
    ): RoleInterface {
        $role = $this->roleRepository->findOneByName($name);

        if (!$role) {
            /** @var RoleInterface $role */
            $role = $this->roleRepository->createNew();
            $role->setName($name);

            $this->entityManager->persist($role);
        }

        $role->setSystem($system);
        $role->setAnonymous($anonymous);

        $this->entityManager->flush();

        return $role;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface
     *
     * @see UsernameNotFoundException
     *
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        if (self::TEST_USER_USERNAME === $username) {
            return $this->getUser();
        }

        return $this->userProvider->loadUserByUsername($username);
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (self::TEST_USER_USERNAME === $this->getUser()->getUsername()) {
            return $this->getUser();
        }

        return $this->userProvider->refreshUser($user);
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $this->userProvider->supportsClass($class);
    }
}
