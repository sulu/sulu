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

$phpcr = getenv('SULU_PHPCR');
$phpcr = $phpcr ?: 'jackrabbit';
$orm = getenv('SULU_ORM');
$orm = $orm ?: 'mysql';

$loader->import('parameters.yml');
$loader->import('context_' . $context . '.yml');
$loader->import('phpcr_' . $phpcr . '.yml');
$loader->import('orm_' . $orm . '.yml');
