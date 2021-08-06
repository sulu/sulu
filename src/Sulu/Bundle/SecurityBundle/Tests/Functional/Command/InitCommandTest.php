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

use Sulu\Bundle\SecurityBundle\Command\InitCommand;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class InitCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $application = new Application(static::$kernel);

        $this->roleRepository = $this->getContainer()->get('sulu.repository.role');

        $initCommand = new InitCommand(
            $this->getContainer()->get('doctrine.orm.entity_manager'),
            $this->roleRepository,
            $this->getContainer()->get('sulu_admin.admin_pool')
        );
        $initCommand->setApplication($application);
        $this->tester = new CommandTester($initCommand);
    }

    public function testCreateAnonymousRole(): void
    {
        $this->runCommand();

        $this->assertStringContainsString(
            '[+] Create anonymous role in system "Sulu CMF" as "Anonymous User Sulu CMF".',
            $this->tester->getDisplay()
        );

        $this->assertStringContainsString(
            '[OK] Created "1" new anonymous roles.',
            $this->tester->getDisplay()
        );

        $this->assertAnonymousRole('Sulu CMF');
    }

    public function testUpdateAnonymousRole(): void
    {
        $this->createAnonymousRole('Sulu CMF');
        $this->getEntityManager()->flush();

        $this->runCommand();

        $this->assertStringContainsString(
            '[*] Anonymous role named "Anonymous User Sulu CMF" in system "Sulu CMF" was updated.',
            $this->tester->getDisplay()
        );

        $this->assertStringContainsString(
            '[OK] Updated "1" anonymous roles.',
            $this->tester->getDisplay()
        );

        $this->assertAnonymousRole('Sulu CMF');
    }

    private function assertAnonymousRole(string $system): void
    {
        /** @var Role $role */
        $role = $this->roleRepository->findOneBy(['system' => $system]);

        $this->assertSame($system, $role->getSystem());
        $this->assertTrue($role->getAnonymous());

        $permissions = $role->getPermissions();

        $this->assertCount(2, $permissions);

        /** @var Permission|null $webspacePermission */
        $webspacePermission = $permissions->filter(function(Permission $permission) {
            return 'sulu.webspaces.sulu_io' === $permission->getContext();
        })->first();
        $this->assertNotNull($webspacePermission);
        $this->assertSame(127, $webspacePermission->getPermissions());

        $collectionPermissions = $permissions->filter(function(Permission $permission) {
            return 'sulu.media.collections' === $permission->getContext();
        })->first() ?: null;
        $this->assertNotNull($collectionPermissions);
        $this->assertSame(127, $collectionPermissions->getPermissions());
    }

    private function createAnonymousRole(string $system): RoleInterface
    {
        $role = $this->roleRepository->createNew();
        $role->setAnonymous(true);
        $role->setName('Anonymous User ' . $system);
        $role->setSystem($system);

        $this->getEntityManager()->persist($role);

        return $role;
    }

    private function runCommand(): void
    {
        $this->tester->execute(
            [],
            ['interactive' => false]
        );
    }
}
