<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 *
 * Read versions from composer.lock and composer.json.
 */
class SuluVersionPass implements CompilerPassInterface
{
    private const SULU_DEFAULT_VERSION = '_._._';

    public function process(ContainerBuilder $container)
    {
        $dir = \realpath($container->getParameter('kernel.project_dir'));

        $container->setParameter('sulu.version', \is_string($dir) ? $this->getSuluVersion($dir) : self::SULU_DEFAULT_VERSION);
        $container->setParameter('app.version', \is_string($dir) ? $this->getAppVersion($dir) : null);
    }

    /**
     * Read composer.lock file and return version of sulu.
     */
    private function getSuluVersion(string $dir): string
    {
        $composerFile = new SplFileInfo($dir . '/composer.lock', '', '');
        if (!$composerFile->isFile()) {
            return self::SULU_DEFAULT_VERSION;
        }

        $composer = \json_decode($composerFile->getContents(), true);

        if (!\is_array($composer)
            || !\is_array($composer['packages'] ?? null)
        ) {
            return self::SULU_DEFAULT_VERSION;
        }

        foreach ($composer['packages'] as $package) {
            if (!\is_array($package)
                || 'sulu/sulu' !== ($package['name'] ?? null)
            ) {
                continue;
            }

            if (!\is_string($package['version'])) {
                break;
            }

            return $package['version'];
        }

        return self::SULU_DEFAULT_VERSION;
    }

    /**
     * Read composer.json file and return version of app.
     */
    private function getAppVersion(string $dir): ?string
    {
        $composerFile = new SplFileInfo($dir . '/composer.json', '', '');
        if (!$composerFile->isFile()) {
            return null;
        }

        $composerJson = \json_decode($composerFile->getContents(), true);

        if (!\is_array($composerJson)
            || !\array_key_exists('version', $composerJson)
            || !\is_string($composerJson['version'])
        ) {
            return null;
        }

        return $composerJson['version'];
    }
}
