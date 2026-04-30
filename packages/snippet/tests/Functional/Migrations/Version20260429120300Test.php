<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Snippet\Migrations\Version20260429120300;

class Version20260429120300Test extends SuluTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->connection = self::getEntityManager()->getConnection();
    }

    public function testFanoutsLegacyContextToPerWebspaceRows(): void
    {
        $roleId = $this->createRoleId('fanout');

        $this->insertPermission($roleId, 'sulu.webspaces.example.pages', 127);
        $this->insertPermission($roleId, 'sulu.webspaces.other.pages', 64);
        $this->insertPermission($roleId, 'sulu.snippet.snippet_areas', 31);

        $this->runMigration();

        $contexts = $this->fetchContextsForRole($roleId);

        self::assertContains('sulu.webspaces.example.snippet-areas', $contexts);
        self::assertContains('sulu.webspaces.other.snippet-areas', $contexts);
        self::assertNotContains('sulu.snippet.snippet_areas', $contexts);

        $permissions = $this->connection->createQueryBuilder()
            ->select('permissions')
            ->from('se_permissions')
            ->where('idRoles = :roleId')
            ->andWhere('context = :context')
            ->setParameter('roleId', $roleId)
            ->setParameter('context', 'sulu.webspaces.example.snippet-areas')
            ->executeQuery()
            ->fetchOne();

        self::assertNotFalse($permissions);
        self::assertEquals(31, $permissions);
    }

    public function testDoesNotOverwriteExistingPerWebspaceRow(): void
    {
        $roleId = $this->createRoleId('preserve-existing');

        $this->insertPermission($roleId, 'sulu.webspaces.example.pages', 127);
        $this->insertPermission($roleId, 'sulu.webspaces.example.snippet-areas', 1);
        $this->insertPermission($roleId, 'sulu.snippet.snippet_areas', 127);

        $this->runMigration();

        $permissions = $this->connection->createQueryBuilder()
            ->select('permissions')
            ->from('se_permissions')
            ->where('idRoles = :roleId')
            ->andWhere('context = :context')
            ->setParameter('roleId', $roleId)
            ->setParameter('context', 'sulu.webspaces.example.snippet-areas')
            ->executeQuery()
            ->fetchOne();

        self::assertNotFalse($permissions);
        self::assertEquals(1, $permissions);
        self::assertNotContains('sulu.snippet.snippet_areas', $this->fetchContextsForRole($roleId));
    }

    public function testIsNoOpWhenLegacyRowIsAbsent(): void
    {
        $roleId = $this->createRoleId('already-migrated');
        $this->insertPermission($roleId, 'sulu.webspaces.example.pages', 127);
        $this->insertPermission($roleId, 'sulu.webspaces.example.snippet-areas', 31);

        $this->runMigration();

        self::assertSame(
            ['sulu.webspaces.example.pages', 'sulu.webspaces.example.snippet-areas'],
            $this->fetchContextsForRole($roleId),
        );
    }

    public function testRemovesOrphanedLegacyRowEvenWithoutDiscoverableWebspaceKeys(): void
    {
        $roleId = $this->createRoleId('orphan');
        $this->insertPermission($roleId, 'sulu.snippet.snippet_areas', 127);

        $this->runMigration();

        self::assertNotContains('sulu.snippet.snippet_areas', $this->fetchContextsForRole($roleId));
    }

    public function testIsIdempotentOnRepeatedRuns(): void
    {
        $roleId = $this->createRoleId('idempotent');
        $this->insertPermission($roleId, 'sulu.webspaces.example.pages', 127);
        $this->insertPermission($roleId, 'sulu.snippet.snippet_areas', 31);

        $this->runMigration();
        $afterFirst = $this->fetchContextsForRole($roleId);

        $this->runMigration();
        $afterSecond = $this->fetchContextsForRole($roleId);

        self::assertSame($afterFirst, $afterSecond);
    }

    private function runMigration(): void
    {
        $migration = new Version20260429120300($this->connection, new NullLogger());
        $migration->up($this->connection->createSchemaManager()->introspectSchema());
    }

    private function createRoleId(string $tag): int
    {
        $em = self::getEntityManager();

        $role = new Role();
        $role->setName('test_role_' . $tag);
        $role->setKey('test_role_' . $tag);
        $role->setSystem('Sulu');
        $role->setAnonymous(false);
        $em->persist($role);
        $em->flush();

        return (int) $role->getId();
    }

    private function insertPermission(int $roleId, string $context, int $permissions): void
    {
        $this->connection->createQueryBuilder()
            ->insert('se_permissions')
            ->values([
                'context' => ':context',
                'permissions' => ':permissions',
                'idRoles' => ':roleId',
            ])
            ->setParameter('context', $context)
            ->setParameter('permissions', $permissions)
            ->setParameter('roleId', $roleId)
            ->executeStatement();
    }

    /**
     * @return list<string>
     */
    private function fetchContextsForRole(int $roleId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('context')
            ->from('se_permissions')
            ->where('idRoles = :roleId')
            ->orderBy('context')
            ->setParameter('roleId', $roleId)
            ->executeQuery()
            ->fetchAllAssociative();

        $contexts = [];
        foreach ($rows as $row) {
            $context = $row['context'];
            self::assertIsString($context);
            $contexts[] = $context;
        }

        return $contexts;
    }
}
