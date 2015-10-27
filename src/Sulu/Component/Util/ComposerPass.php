<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\SplFileInfo;

/**
 */
class ComposerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $rootDirectory = realpath($container->getParameter('kernel.root_dir') . '/..');
        $composerFile = new SplFileInfo($rootDirectory . '/composer.json', '', '');

        if (!$composerFile->isFile()) {
            return;
        }

        $composerJson = json_decode($composerFile->getContents(), true);

        $container->setParameter(
            'sulu.version',
            $this->getSuluVersion($composerJson)
        );

        $container->setParameter(
            'sulu.vendor_dir',
            $this->getVendorDirectory($composerJson, $rootDirectory)
        );
    }

    /**
     * @param $composerJson
     * @param $rootDirectory
     *
     * @return string
     */
    private function getVendorDirectory($composerJson, $rootDirectory)
    {
        if (array_key_exists('config', $composerJson)
            && array_key_exists('vendor-dir', $composerJson['config'])
        ) {
            return $composerJson['config']['vendor-dir'];
        }

        return $rootDirectory . '/vendor';
    }

    /**
     * @param $composerJson
     *
     * @return string
     */
    private function getSuluVersion($composerJson)
    {
        return array_key_exists('version', $composerJson) ? $composerJson['version'] : '_._._';
    }
}
