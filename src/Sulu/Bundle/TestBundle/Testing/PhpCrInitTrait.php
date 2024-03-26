<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use PHPCR\ImportUUIDBehaviorInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use Sulu\Bundle\DocumentManagerBundle\Initializer\Initializer;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

trait PhpCrInitTrait
{
    private static $workspaceInitialized = false;

    private static function getInitializer(): Initializer
    {
        return static::getContainer()->get('sulu_document_manager.initializer');
    }

    private static function getPhpcrDefaultSession(): SessionInterface
    {
        return static::getContainer()->get('doctrine_phpcr.session');
    }

    private static function getPhpcrLiveSession(): SessionInterface
    {
        return static::getContainer()->get('doctrine_phpcr.live_session');
    }

    /**
     * Initialize / reset the Sulu PHPCR environment.
     *
     * NOTE: We could use the document initializer here rather than manually creating
     *       the webspace nodes, but it currently adds more overhead and offers
     *       no control over *which* webspaces are created, see
     *       https://github.com/sulu-io/sulu/pull/2063 for a solution.
     */
    protected static function initPhpcr(): void
    {
        $session = static::getPhpcrDefaultSession();
        $liveSession = static::getPhpcrLiveSession();

        if (!self::$workspaceInitialized) {
            static::getInitializer()->initialize(null, true);

            static::dumpPhpcr($session, 'default');
            static::dumpPhpcr($liveSession, 'live');
            self::$workspaceInitialized = true;

            return;
        }

        static::importSession($session, 'default');
        if ($session->getWorkspace()->getName() !== $liveSession->getWorkspace()->getName()) {
            static::importSession($liveSession, 'live');
        }
    }

    /**
     * @internal
     */
    private static function dumpPhpcr(SessionInterface $session, string $workspace): void
    {
        $initializerDump = static::getInitializerDumpFilePath($workspace);

        $filesystem = new Filesystem();
        if (!$filesystem->exists(\dirname($initializerDump))) {
            $filesystem->mkdir(\dirname($initializerDump));
        }

        $handle = \fopen($initializerDump, 'w');
        if (!$handle) {
            throw new \InvalidArgumentException('Could not open ' . $initializerDump);
        }

        $session->exportSystemView('/cmf', $handle, false, false);
        \fclose($handle);
    }

    /**
     * @internal
     *
     * Initialize / reset Sulu PHPCR environment for given session
     */
    private static function importSession(SessionInterface $session, string $workspace): void
    {
        $initializerDump = static::getInitializerDumpFilePath($workspace);

        if ($session->nodeExists('/cmf')) {
            NodeHelper::purgeWorkspace($session);
            $session->save();
        }

        $session->importXml('/', $initializerDump, ImportUUIDBehaviorInterface::IMPORT_UUID_COLLISION_THROW);
        $session->save();
    }

    /**
     * @internal
     */
    private static function getInitializerDumpFilePath(string $workspace): string
    {
        if (!static::$kernel instanceof SuluKernel) {
            throw new \TypeError(
                'Expected that Kernel is "%s" but "%s" given.',
                SuluKernel::class,
                \get_class(static::$kernel)
            );
        }

        return match ($workspace) {
            'live' => static::$kernel->getCommonCacheDir() . '/initial_live.xml',
            'default' => static::$kernel->getCommonCacheDir() . '/initial.xml',
            default => throw new \InvalidArgumentException(\sprintf('Workspace "%s" is not a valid option', $workspace)),
        };
    }

    abstract public static function getContainer(): ContainerInterface;
}
