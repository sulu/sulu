<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\HttpKernel\Kernel;

$filesystem = new \Symfony\Component\Filesystem\Filesystem();

$context = $container->getParameter('sulu.context');
$path = __DIR__ . \DIRECTORY_SEPARATOR;
if (!$filesystem->exists($path . 'parameters.yml')) {
    $filesystem->copy($path . 'parameters.yml.dist', $path . 'parameters.yml');
}
$loader->import('parameters.yml');
$loader->import('context_' . $context . '.yml');

if (\class_exists(\Swift_Mailer::class)) {
    $loader->import('swiftmailer.yml');
}

if ('admin' === $context) {
    if (\version_compare(Kernel::VERSION, '6.0.0', '>=')) {
        $loader->import('security-6.yml');
    } else {
        $loader->import('security-5-4.yml');
    }
}

if (\version_compare(Kernel::VERSION, '6.0.0', '>=')) {
    $loader->import('symfony-6.yml');
} else {
    $loader->import('symfony-5-4.yml');
}
