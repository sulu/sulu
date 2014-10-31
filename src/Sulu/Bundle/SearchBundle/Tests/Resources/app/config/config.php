<?php

$container->setParameter('cmf_testing.bundle_fqn', 'Sulu\Bundle\SearchBundle\SuluSearchBundle');
$loader->import(CMF_TEST_CONFIG_DIR.'/default.php');
$loader->import(CMF_TEST_CONFIG_DIR.'/phpcr_odm.php');
$loader->import(__DIR__.'/sulusearchbundle.yml');
