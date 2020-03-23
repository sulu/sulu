<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Sulu\Bundle\CoreBundle\Doctrine\SQLite\ForeignKeyActivationSubscriber;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
class SQLiteCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$this->isSQLiteConnection($container)) {
            return;
        }

        $sqliteForeignKeyActivationSubscriberDefinition = new Definition(
            ForeignKeyActivationSubscriber::class
        );

        $sqliteForeignKeyActivationSubscriberDefinition->addTag(
            'doctrine.event_subscriber',
            [
                'connection' => 'default',
            ]
        );

        $container->setDefinition(
            'sulu_core.sqlite_foreign_key_activation_subscriber',
            $sqliteForeignKeyActivationSubscriberDefinition
        );
    }

    private function isSQLiteConnection(ContainerBuilder $container)
    {
        $connectionDefinition = $container->getDefinition('doctrine.dbal.default_connection');

        $configuration = $connectionDefinition->getArgument(0);

        if (empty($configuration['url'])) {
            // if no url is set check if driver is set to sqlite or pdo_sqlite
            if (isset($configuration['driver'])
                && false !== strpos(strtolower($configuration['driver']), 'sqlite')
            ) {
                return true;
            }

            return false;
        }

        $databaseUrl = $container->resolveEnvPlaceholders($configuration['url'], true);

        if (0 !== strpos(strtolower($databaseUrl), 'sqlite')) {
            return false;
        }

        return true;
    }
}
