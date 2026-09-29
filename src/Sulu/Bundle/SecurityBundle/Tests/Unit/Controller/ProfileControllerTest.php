<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Controller;

use Doctrine\Persistence\ObjectManager;
use FOS\RestBundle\View\ViewHandlerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Controller\ProfileController;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\SecurityBundle\TwoFactor\TwoFactorForceChecker;
use Sulu\Component\Security\Authentication\UserSettingRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProfileControllerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TokenStorageInterface>
     */
    private $tokenStorage;

    /**
     * @var ObjectProphecy<ObjectManager>
     */
    private $objectManager;

    /**
     * @var ObjectProphecy<ViewHandlerInterface>
     */
    private $viewHandler;

    /**
     * @var ObjectProphecy<UserSettingRepositoryInterface>
     */
    private $userSettingRepository;

    private RecordingUserManager $userManager;

    public function setUp(): void
    {
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->viewHandler = $this->prophesize(ViewHandlerInterface::class);
        $this->userSettingRepository = $this->prophesize(UserSettingRepositoryInterface::class);
        $this->userManager = new RecordingUserManager();

        $this->viewHandler->handle(Argument::any())->willReturn(new Response());
    }

    public function testPutRejectsDisablingTwoFactorForAForcedUserBeforeSaving(): void
    {
        $user = $this->createUser('admin@sulu.io', 'totp', ['totpSecret' => 'CONFIRMED']);
        $this->authenticateAs($user);

        $this->expectException(AccessDeniedHttpException::class);

        try {
            $controller = $this->createController(new TwoFactorForceChecker('/@sulu\.io$/', true, ['totp']));
            $controller->putAction($this->createRequest([
                'firstName' => 'Admin',
                'lastName' => 'User',
                'username' => 'admin',
                'email' => 'admin@sulu.io',
                'locale' => 'en',
                'twoFactor' => ['method' => null],
            ]));
        } finally {
            $this->assertFalse($this->userManager->saveCalled);
        }
    }

    public function testPutRejectsLeavingTheForcedPatternEvenWhenTheMethodIsKept(): void
    {
        $user = $this->createUser('admin@sulu.io', 'totp', ['totpSecret' => 'CONFIRMED']);
        $this->authenticateAs($user);

        $this->expectException(AccessDeniedHttpException::class);

        try {
            $controller = $this->createController(new TwoFactorForceChecker('/@sulu\.io$/', true, ['totp']));
            $controller->putAction($this->createRequest([
                'firstName' => 'Admin',
                'lastName' => 'User',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'locale' => 'en',
                'twoFactor' => ['method' => 'totp'],
            ]));
        } finally {
            $this->assertFalse($this->userManager->saveCalled);
        }
    }

    public function testPutAllowsAForcedUserToKeepTheMethodAndStayInThePattern(): void
    {
        $user = $this->createUser('admin@sulu.io', 'totp', ['totpSecret' => 'CONFIRMED']);
        $this->authenticateAs($user);

        $controller = $this->createController(new TwoFactorForceChecker('/@sulu\.io$/', true, ['totp']));
        $controller->putAction($this->createRequest([
            'firstName' => 'Admin',
            'lastName' => 'User',
            'username' => 'admin',
            'email' => 'still-admin@sulu.io',
            'locale' => 'en',
            'twoFactor' => ['method' => 'totp'],
        ]));

        $this->assertTrue($this->userManager->saveCalled);
    }

    public function testPutAllowsDisablingTwoFactorForAnUnforcedUser(): void
    {
        $user = $this->createUser('admin@example.com', 'totp', ['totpSecret' => 'CONFIRMED']);
        $this->authenticateAs($user);

        $controller = $this->createController(new TwoFactorForceChecker('/@sulu\.io$/', true, ['totp']));
        $controller->putAction($this->createRequest([
            'firstName' => 'Admin',
            'lastName' => 'User',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'locale' => 'en',
            'twoFactor' => ['method' => null],
        ]));

        $this->assertTrue($this->userManager->saveCalled);
        $this->assertNull($user->getTwoFactor());
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createUser(string $email, ?string $method = null, array $options = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setContact(new Contact());

        if ($method) {
            $twoFactor = new UserTwoFactor($user);
            $twoFactor->setMethod($method);
            $twoFactor->setOptions($options);
            $user->setTwoFactor($twoFactor);
        }

        return $user;
    }

    private function authenticateAs(User $user): void
    {
        $token = $this->prophesize(TokenInterface::class);
        $token->getUser()->willReturn($user);
        $this->tokenStorage->getToken()->willReturn($token->reveal());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createRequest(array $data): Request
    {
        return new Request([], $data);
    }

    private function createController(?TwoFactorForceChecker $twoFactorForceChecker): ProfileController
    {
        return new ProfileController(
            $this->tokenStorage->reveal(),
            $this->objectManager->reveal(),
            $this->viewHandler->reveal(),
            $this->userSettingRepository->reveal(),
            $this->userManager,
            User::class,
            Contact::class,
            $twoFactorForceChecker
        );
    }
}

/**
 * "save" is not part of UserManagerInterface (a pre-existing gap, ignored in the baseline),
 * so a Prophecy double of the interface can not stub it: it has to be a real implementation.
 */
class RecordingUserManager implements UserManagerInterface
{
    public bool $saveCalled = false;

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data, ?string $locale, ?int $id = null, bool $patch = false): void
    {
        $this->saveCalled = true;
    }

    public function getUsernameByUserId($id): string
    {
        return '';
    }

    public function getFullNameByUserId($id): string
    {
        return '';
    }
}
