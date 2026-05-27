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

namespace Sulu\Bundle\SecurityBundle\Migrations;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Rename legacy permission contexts:
 * - sulu.modules.articles -> sulu.article.articles
 * - sulu.global.snippets  -> sulu.snippet.snippets
 *
 * The (context, idRoles) pair is unique. If a role already has a row with the new
 * context, the legacy row is deleted instead of renamed to avoid a constraint violation.
 */
final class Version20260429120100 extends AbstractMigration
{
    /**
     * @var array<string, string>
     */
    private const RENAMES = [
        'sulu.modules.articles' => 'sulu.article.articles',
        'sulu.global.snippets' => 'sulu.snippet.snippets',
    ];

    public function getDescription(): string
    {
        return 'Rename legacy permission contexts (sulu.modules.articles, sulu.global.snippets)';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('se_permissions')) {
            return;
        }

        foreach (self::RENAMES as $old => $new) {
            $this->renameContext($old, $new);
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('se_permissions')) {
            return;
        }

        foreach (self::RENAMES as $old => $new) {
            $this->renameContext($new, $old);
        }
    }

    private function renameContext(string $oldContext, string $newContext): void
    {
        $rolesWithNewContext = $this->connection->createQueryBuilder()
            ->select('idRoles')
            ->from('se_permissions')
            ->where('context = :newContext')
            ->setParameter('newContext', $newContext)
            ->executeQuery()
            ->fetchFirstColumn();

        if ([] !== $rolesWithNewContext) {
            $this->connection->createQueryBuilder()
                ->delete('se_permissions')
                ->where('context = :oldContext')
                ->andWhere('idRoles IN (:roleIds)')
                ->setParameter('oldContext', $oldContext)
                ->setParameter('roleIds', $rolesWithNewContext, ArrayParameterType::INTEGER)
                ->executeStatement();
        }

        $this->connection->createQueryBuilder()
            ->update('se_permissions')
            ->set('context', ':newContext')
            ->where('context = :oldContext')
            ->setParameter('newContext', $newContext)
            ->setParameter('oldContext', $oldContext)
            ->executeStatement();
    }
}
