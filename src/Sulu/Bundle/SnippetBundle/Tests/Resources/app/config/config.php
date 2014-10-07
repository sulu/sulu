<?php

$container->setParameter('sulu.context', 'admin');
// Tests/Resources/app/config/config.php
// $container->setParameter('cmf_testing.bundle_fqn', 'Sulu\Bundle\SnippetBundle\SuluSnippetBundle');
$loader->import(CMF_TEST_CONFIG_DIR.'/default.php');
$loader->import(__DIR__.'/sulu.yml');

