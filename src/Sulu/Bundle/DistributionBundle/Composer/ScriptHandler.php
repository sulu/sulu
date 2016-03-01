<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DistributionBundle\Composer;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Contains hooks executed during Composer updates/installs.
 */
class ScriptHandler
{
    private static $options = [
        'symfony-app-dir' => 'app',
        'sulu-dist-install' => true,
    ];

    /**
     * Copies ".dist" files to the local Sulu installation if they don't exist
     * already.
     *
     * @param Event $event The Composer event.
     */
    public static function installDistFiles(Event $event)
    {
        $options = self::getOptions($event);

        if (!$options['sulu-dist-install']) {
            return;
        }

        $filesystem = new Filesystem();
        $resourceDir = $options['symfony-app-dir'] . '/Resources';

        if (!$filesystem->exists($resourceDir . '/webspaces/sulu.io.xml')) {
            $filesystem->copy(
                $resourceDir . '/webspaces/sulu.io.xml.dist',
                $resourceDir . '/webspaces/sulu.io.xml'
            );
        }

        if (!$filesystem->exists($resourceDir . '/pages/default.xml')) {
            $filesystem->copy(
                $resourceDir . '/pages/default.xml.dist',
                $resourceDir . '/pages/default.xml'
            );
        }

        if (!$filesystem->exists($resourceDir . '/pages/overview.xml')) {
            $filesystem->copy(
                $resourceDir . '/pages/overview.xml.dist',
                $resourceDir . '/pages/overview.xml'
            );
        }

        if (!$filesystem->exists($resourceDir . '/snippets/default.xml')) {
            $filesystem->copy(
                $resourceDir . '/snippets/default.xml.dist',
                $resourceDir . '/snippets/default.xml'
            );
        }
    }

    private static function getOptions(Event $event)
    {
        return array_replace(static::$options, $event->getComposer()->getPackage()->getExtra());
    }

    private function __construct()
    {
    }
}
