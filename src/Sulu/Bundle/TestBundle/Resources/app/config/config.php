<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Filesystem\Filesystem;

return static function(PhpFileLoader $loader, ContainerBuilder $container) {
    $filesystem = new Filesystem();

    $context = $container->getParameter('sulu.context');
    $loader->import('context_' . $context . '.yml');

    if ('admin' === $context) {
        $loader->import('security-6.yml');
    }

    if (InstalledVersions::satisfies(new VersionParser(), 'doctrine/doctrine-bundle', '^2.0')) {
        $loader->import('doctrine_2.yml');
    }

    $loader->import('symfony-6.yml');
};
