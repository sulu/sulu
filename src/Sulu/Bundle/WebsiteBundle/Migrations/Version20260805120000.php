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

namespace Sulu\Bundle\WebsiteBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cache clearing used to be gated by the LIVE permission on the webspace security context
 * (`sulu.webspaces.<webspace>`). It is now gated by the DELETE permission on a dedicated
 * cache security context (`sulu.webspaces.<webspace>.cache`). Grants the new permission to
 * every role that already had LIVE access, so upgrading does not silently revoke the ability
 * to clear the cache.
 */
final class Version20260805120000 extends AbstractMigration
{
    private const LIVE_PERMISSION = 2;

    private const DELETE_PERMISSION = 8;

    public function getDescription(): string
    {
        return 'Grant the new webspace cache permission to roles that already had LIVE permission on the webspace';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('se_permissions')) {
            return;
        }

        $rows = $this->connection->createQueryBuilder()
            ->select('context', 'permissions', 'idRoles')
            ->from('se_permissions')
            ->where('context LIKE :prefix')
            ->andWhere('context NOT LIKE :nested')
            ->setParameter('prefix', 'sulu.webspaces.%')
            ->setParameter('nested', 'sulu.webspaces.%.%')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            if (0 === ((int) $row['permissions'] & self::LIVE_PERMISSION)) {
                continue;
            }

            $this->grantCachePermission($row['context'] . '.cache', (int) $row['idRoles']);
        }
    }

    public function down(Schema $schema): void
    {
        // Not reversible: the DELETE bit added to `<webspace>.cache` contexts cannot be
        // distinguished from grants an administrator may have added manually since the
        // upgrade, so removing it here could revoke access that was deliberately granted.
    }

    private function grantCachePermission(string $context, int $roleId): void
    {
        $existing = $this->connection->createQueryBuilder()
            ->select('id', 'permissions')
            ->from('se_permissions')
            ->where('context = :context')
            ->andWhere('idRoles = :roleId')
            ->setParameter('context', $context)
            ->setParameter('roleId', $roleId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $existing) {
            $this->connection->createQueryBuilder()
                ->insert('se_permissions')
                ->values([
                    'context' => ':context',
                    'permissions' => ':permissions',
                    'idRoles' => ':roleId',
                ])
                ->setParameter('context', $context)
                ->setParameter('permissions', self::DELETE_PERMISSION)
                ->setParameter('roleId', $roleId)
                ->executeStatement();

            return;
        }

        $permissions = (int) $existing['permissions'] | self::DELETE_PERMISSION;
        if ($permissions === (int) $existing['permissions']) {
            return;
        }

        $this->connection->createQueryBuilder()
            ->update('se_permissions')
            ->set('permissions', ':permissions')
            ->where('id = :id')
            ->setParameter('permissions', $permissions)
            ->setParameter('id', $existing['id'])
            ->executeStatement();
    }
}
