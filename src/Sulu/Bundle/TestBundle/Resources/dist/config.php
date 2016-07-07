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

if ($filesystem->exists('parameters.yml')) {
    $loader->import('parameters.yml');
} else {
    $loader->import('parameters.yml.dist');
}
$loader->import('context_' . $context . '.yml');
