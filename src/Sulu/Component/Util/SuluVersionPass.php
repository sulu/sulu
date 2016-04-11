<?php

/*
 * This file is part of Sulu.
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

class SuluVersionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter(
            'sulu.version',
            $this->getVersionFromComposerJson(realpath($container->getParameter('kernel.root_dir') . '/..'))
        );
    }

    private function getVersionFromComposerJson($dir)
    {
        $version = '_._._';

        /** @var SplFileInfo $composerFile */
        $composerFile = new SplFileInfo($dir . '/composer.json', '', '');
        if ($composerFile->isFile()) {
            $composerJson = json_decode($composerFile->getContents());

            return $composerJson->version;
        }

        return $version;
    }
}
