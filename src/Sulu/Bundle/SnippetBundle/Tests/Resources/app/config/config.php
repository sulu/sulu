<?php

$container->setParameter('sulu.context', 'admin');
// Tests/Resources/app/config/config.php
// $container->setParameter('cmf_testing.bundle_fqn', 'Sulu\Bundle\SnippetBundle\SuluSnippetBundle');
$loader->import(CMF_TEST_CONFIG_DIR.'/dist/parameters.yml');
$loader->import(CMF_TEST_CONFIG_DIR.'/dist/framework.yml');
$loader->import(CMF_TEST_CONFIG_DIR.'/dist/doctrine.yml');
$loader->import(CMF_TEST_CONFIG_DIR.'/dist/monolog.yml');
$loader->import(__DIR__.'/sulu.yml');

