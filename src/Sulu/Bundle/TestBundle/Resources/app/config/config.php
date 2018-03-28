<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$filesystem = new \Symfony\Component\Filesystem\Filesystem();

$context = $container->getParameter('sulu.context');
$path = __DIR__ . DIRECTORY_SEPARATOR;
if (!$filesystem->exists($path . 'parameters.yml')) {
    $filesystem->copy($path . 'parameters.yml.dist', $path . 'parameters.yml');
}
$loader->import('parameters.yml');
$loader->import('context_' . $context . '.yml');
