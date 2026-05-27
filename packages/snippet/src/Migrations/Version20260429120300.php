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

namespace Sulu\Snippet\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migrate snippet area permissions from the global "sulu.snippet.snippet_areas" context to
 * per-webspace "sulu.webspaces.{webspaceKey}.snippet-areas" rows. Webspace keys are derived
 * from existing "sulu.webspaces.*" rows in se_permissions. Existing per-webspace rows for
 * the same role are preserved (the (context, idRoles) pair is unique).
 */
final class Version20260429120300 extends AbstractMigration
{
    private const OLD_CONTEXT = 'sulu.snippet.snippet_areas';

    private const WEBSPACE_PREFIX = 'sulu.webspaces.';

    private const SNIPPET_AREAS_SUFFIX = '.snippet-areas';

    public function getDescription(): string
    {
        return 'Migrate snippet area permissions to per-webspace contexts (sulu.webspaces.{webspace}.snippet-areas)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('se_permissions')) {
            return;
        }

        $oldRows = $this->connection->createQueryBuilder()
            ->select('idRoles', 'permissions')
            ->from('se_permissions')
            ->where('context = :oldContext')
            ->setParameter('oldContext', self::OLD_CONTEXT)
            ->executeQuery()
            ->fetchAllAssociative();

        if ([] === $oldRows) {
            return;
        }

        foreach ($this->discoverWebspaceKeys() as $webspaceKey) {
            $newContext = self::WEBSPACE_PREFIX . $webspaceKey . self::SNIPPET_AREAS_SUFFIX;
            $existingRoleIds = $this->fetchRoleIdsForContext($newContext);

            foreach ($oldRows as $row) {
                $roleId = $row['idRoles'];
                if (!\is_int($roleId) && !\is_string($roleId)) {
                    continue;
                }
                $roleId = (int) $roleId;
                if (\in_array($roleId, $existingRoleIds, true)) {
                    continue;
                }

                $this->connection->createQueryBuilder()
                    ->insert('se_permissions')
                    ->values([
                        'context' => ':context',
                        'permissions' => ':permissions',
                        'idRoles' => ':roleId',
                    ])
                    ->setParameter('context', $newContext)
                    ->setParameter('permissions', $row['permissions'])
                    ->setParameter('roleId', $roleId)
                    ->executeStatement();
            }
        }

        $this->connection->createQueryBuilder()
            ->delete('se_permissions')
            ->where('context = :oldContext')
            ->setParameter('oldContext', self::OLD_CONTEXT)
            ->executeStatement();
    }

    public function down(Schema $schema): void
    {
        // A single global row cannot be reliably reconstructed from per-webspace rows.
    }

    /**
     * @return list<string>
     */
    private function discoverWebspaceKeys(): array
    {
        $contexts = $this->connection->createQueryBuilder()
            ->select('DISTINCT context')
            ->from('se_permissions')
            ->where('context LIKE :pattern')
            ->setParameter('pattern', self::WEBSPACE_PREFIX . '%')
            ->executeQuery()
            ->fetchFirstColumn();

        $keys = [];
        foreach ($contexts as $context) {
            if (!\is_string($context)) {
                continue;
            }
            $remainder = \substr($context, \strlen(self::WEBSPACE_PREFIX));
            $key = \explode('.', $remainder, 2)[0];
            if ('' !== $key) {
                $keys[$key] = true;
            }
        }

        return \array_keys($keys);
    }

    /**
     * @return list<int>
     */
    private function fetchRoleIdsForContext(string $context): array
    {
        $roleIds = $this->connection->createQueryBuilder()
            ->select('idRoles')
            ->from('se_permissions')
            ->where('context = :context')
            ->setParameter('context', $context)
            ->executeQuery()
            ->fetchFirstColumn();

        $result = [];
        foreach ($roleIds as $roleId) {
            if (\is_int($roleId) || \is_string($roleId)) {
                $result[] = (int) $roleId;
            }
        }

        return $result;
    }
}
