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

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Migrations;

use Doctrine\DBAL\Connection;
use Psr\Log\NullLogger;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Migrations\Version20260429120100;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class Version20260429120100Test extends SuluTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();
        $this->connection = self::getEntityManager()->getConnection();
    }

    public function testRenamesLegacyContextsToNewContexts(): void
    {
        $roleId = $this->createRoleId('legacy');
        $this->insertPermission($roleId, 'sulu.modules.articles', 127);
        $this->insertPermission($roleId, 'sulu.global.snippets', 64);

        $this->runMigration();

        self::assertSame(
            ['sulu.article.articles', 'sulu.snippet.snippets'],
            $this->fetchContextsForRole($roleId),
        );
    }

    public function testIsNoOpWhenAlreadyMigrated(): void
    {
        $roleId = $this->createRoleId('already-migrated');
        $this->insertPermission($roleId, 'sulu.article.articles', 127);
        $this->insertPermission($roleId, 'sulu.snippet.snippets', 64);

        $this->runMigration();

        self::assertSame(
            ['sulu.article.articles', 'sulu.snippet.snippets'],
            $this->fetchContextsForRole($roleId),
        );
    }

    public function testRemovesLegacyDuplicateWhenNewContextAlreadyExistsForRole(): void
    {
        $roleId = $this->createRoleId('partial');
        $this->insertPermission($roleId, 'sulu.modules.articles', 127);
        $this->insertPermission($roleId, 'sulu.article.articles', 31);

        $this->runMigration();

        $rows = $this->connection->createQueryBuilder()
            ->select('context', 'permissions')
            ->from('se_permissions')
            ->where('idRoles = :roleId')
            ->orderBy('context')
            ->setParameter('roleId', $roleId)
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $rows);
        self::assertSame('sulu.article.articles', $rows[0]['context']);
        self::assertEquals(31, $rows[0]['permissions']);
    }

    public function testIsIdempotentOnRepeatedRuns(): void
    {
        $roleId = $this->createRoleId('idempotent');
        $this->insertPermission($roleId, 'sulu.modules.articles', 127);

        $this->runMigration();
        $this->runMigration();

        self::assertSame(['sulu.article.articles'], $this->fetchContextsForRole($roleId));
    }

    private function runMigration(): void
    {
        $migration = new Version20260429120100($this->connection, new NullLogger());
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
