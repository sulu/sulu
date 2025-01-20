<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\UserManager;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Security\Exception\EmailNotUniqueException;
use Sulu\Bundle\SecurityBundle\UserManager\UserManager;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authentication\SaltGenerator;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class UserManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var ObjectProphecy<UserRepositoryInterface>
     */
    private $userRepository;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $eventCollector;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    private $objectManager;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var ObjectProphecy<SaltGenerator>
     */
    private $saltGenerator;

    /**
     * @var ObjectProphecy<ContactManager>
     */
    private $contactManager;

    public function setUp(): void
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $passwordHasherFactory = $this->prophesize(PasswordHasherFactoryInterface::class);
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->eventCollector = $this->prophesize(DomainEventCollectorInterface::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->contactManager = $this->prophesize(ContactManager::class);
        $this->saltGenerator = $this->prophesize(SaltGenerator::class);

        $this->userManager = new UserManager(
            $this->objectManager->reveal(),
            $passwordHasherFactory->reveal(),
            $this->roleRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $this->userRepository->reveal(),
            $this->eventCollector->reveal(),
            null
        );
    }

    public function testGetFullNameByUserIdForNonExistingUser(): void
    {
        $this->assertNull($this->userManager->getFullNameByUserId(0));
    }

    public function testValidatePasswordNoPattern(): void
    {
        $this->assertTrue($this->userManager->isValidPassword('test 123'));
        $this->assertFalse($this->userManager->isValidPassword(''));
    }

    public function testValidatePasswordWithPattern(): void
    {
        $userManager = new UserManager(
            $this->objectManager->reveal(),
            null,
            $this->roleRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $this->userRepository->reveal(),
            $this->eventCollector->reveal(),
            '.{8,}'
        );

        $this->assertTrue($userManager->isValidPassword('testtest'));
        $this->assertFalse($userManager->isValidPassword('test'));
    }

    public function testSaveCreatesNewUser(): void
    {
        $data = [
            'username' => 'john.doe',
            'email' => 'john.doe@example.com',
            'password' => '!1a2B3.HdäG2M+f0o',
            'contactId' => 1,
        ];

        $user = new User();
        $contact = new Contact();
        $hashedPassword = '-hashed-password-';

        // Wee need to stub UserRepository instead of the interface, because the interface does not
        // declare 'findUserByEmail'.
        $userRepository = $this->prophesize(UserRepository::class);
        $userRepository->createNew()->willReturn($user);
        $userRepository->findUserByEmail($data['email'])->willThrow(new NoResultException());
        $userRepository->findUserByUsername($data['username'])->willThrow(new NoResultException());

        $passwordHasher = $this->prophesize(PasswordHasherInterface::class);
        $passwordHasher->hash($data['password'])->willReturn($hashedPassword);

        $passwordHasherFactory = $this->prophesize(PasswordHasherFactoryInterface::class);
        $passwordHasherFactory->getPasswordHasher($user)->willReturn($passwordHasher);

        $this->contactManager->findById($data['contactId'])->willReturn($contact);

        $userManager = new UserManager(
            $this->objectManager->reveal(),
            $passwordHasherFactory->reveal(),
            $this->roleRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $userRepository->reveal(),
            $this->eventCollector->reveal(),
            null
        );

        $result = $userManager->save($data, locale: 'en', id: null);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($data['username'], $result->getUsername());
        $this->assertSame($data['email'], $result->getEmail());
        $this->assertSame($hashedPassword, $result->getPassword());
    }

    public function testSaveValidatesEmailUniquenessForNewUser(): void
    {
        $data = [
            'username' => 'john.doe',
            'email' => 'john.doe@example.com',
            'password' => '!1a2B3.HdäG2M+f0o',
        ];

        $user = new User();

        // Wee need to stub UserRepository instead of the interface, because the interface does not
        // declare 'findUserByEmail'.
        $userRepository = $this->prophesize(UserRepository::class);
        $userRepository->createNew()->willReturn($user);
        $userRepository->findUserByEmail($data['email'])->willReturn(new User());

        $userManager = new UserManager(
            $this->objectManager->reveal(),
            null,
            $this->roleRepository->reveal(),
            $this->contactManager->reveal(),
            $this->saltGenerator->reveal(),
            $userRepository->reveal(),
            $this->eventCollector->reveal(),
            null
        );

        $this->expectException(EmailNotUniqueException::class);

        $result = $userManager->save($data, locale: 'en', id: null);
    }
}
