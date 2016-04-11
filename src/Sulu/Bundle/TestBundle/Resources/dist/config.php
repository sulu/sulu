<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$context = $container->getParameter('sulu.context');

$loader->import('parameters.yml');
$loader->import('context_' . $context . '.yml');
