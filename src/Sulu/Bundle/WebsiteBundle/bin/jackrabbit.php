<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

include_once 'vendor/autoload.php';

$options = [
    'url' => 'http://localhost:8080/server',
    'username' => 'admin',
    'password' => 'admin',
    'workspace' => 'default',
];

$parameters = ['jackalope.jackrabbit_uri' => $options['url']];
$factory = new \Jackalope\RepositoryFactoryJackrabbit();
$repository = $factory->getRepository($parameters);
$credentials = new PHPCR\SimpleCredentials($options['username'], $options['password']);

$session = $repository->login($credentials, $options['workspace']);

$workspace = $session->getWorkspace();
$repo = $session->getRepository();

if (!$repo->getDescriptor(PHPCR\RepositoryInterface::OPTION_WORKSPACE_MANAGEMENT_SUPPORTED)) {
    echo
        '<error>Your PHPCR implementation does not support ' .
        'workspace management. Please refer to the documentation ' .
        'of your PHPCR implementation to learn how to create a workspace.</error>'
    ;

    die();
}

try {
    $workspace->createWorkspace('test');
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
