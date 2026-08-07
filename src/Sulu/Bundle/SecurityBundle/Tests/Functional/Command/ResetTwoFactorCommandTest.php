<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Command;

use Sulu\Bundle\SecurityBundle\Command\ResetTwoFactorCommand;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ResetTwoFactorCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $application = new Application($this->getContainer()->get('kernel'));

        $resetTwoFactorCommand = new ResetTwoFactorCommand(
            $this->getContainer()->get('doctrine.orm.entity_manager'),
            $this->getContainer()->get('sulu.repository.user'),
        );
        $resetTwoFactorCommand->setApplication($application);
        $this->tester = new CommandTester($resetTwoFactorCommand);
    }

    public function testResetTwoFactor(): void
    {
        /** @var User $user */
        $user = static::getTestUser();
        $twoFactor = new UserTwoFactor($user);
        $twoFactor->setMethod('totp');
        $twoFactor->setOptions(['totpSecret' => 'SECRET']);
        $user->setTwoFactor($twoFactor);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($twoFactor);
        $entityManager->flush();

        $this->tester->execute(['username' => 'test']);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('was reset', $this->tester->getDisplay());

        $entityManager->clear();
        /** @var User $user */
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => 'test']);
        $this->assertNull($user->getTwoFactor());
    }

    public function testResetTwoFactorWithoutTwoFactor(): void
    {
        static::getTestUser();

        $this->tester->execute(['username' => 'test']);

        $this->assertSame(0, $this->tester->getStatusCode());
        $this->assertStringContainsString('no two factor authentication', $this->tester->getDisplay());
    }

    public function testResetTwoFactorUserNotFound(): void
    {
        $this->tester->execute(['username' => 'not-existing']);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertStringContainsString('not found', $this->tester->getDisplay());
    }
}
