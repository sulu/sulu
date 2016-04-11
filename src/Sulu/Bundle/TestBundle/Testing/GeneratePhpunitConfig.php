<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$source = __DIR__ . '/../../../../../../../phpunit.xml.dist';
$config = [
    'mysql' => [
        'APP_DB' => 'mysql',
    ],
    'pgsql' => [
        'APP_DB' => 'pgsql',
    ],
];

if (!in_array(@$argv[1], array_keys($config))) {
    die('Error:' . "\n\t" . 'Database "' . @$argv[1] . '" not supported.' . "\n" .
        'Usage:' . "\n\t" . 'php tests/' . basename(__FILE__) . ' [' . implode('|', array_keys($config)) . ']' . "\n");
}

$dom = new \DOMDocument('1.0', 'UTF-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->strictErrorChecking = true;
$dom->validateOnParse = true;
$dom->load($source);

$xpath = new \DOMXPath($dom);
$parent = $xpath->query('/phpunit/php')->item(0);
$nodes = $xpath->query('/phpunit/php/var[starts-with(@name,"APP_DB")]');

foreach ($nodes as $node) {
    $parent->removeChild($node);
}

foreach ($config[$argv[1]] as $key => $value) {
    $node = $dom->createElement('var');
    $node->setAttribute('name', $key);
    $node->setAttribute('value', $value);
    $parent->appendChild($node);
}

$destination = str_replace('phpunit.xml.dist', $argv[1] . '.phpunit.xml', $source);
$dom->save($destination);

echo 'Created:' . "\n\t" . realpath($destination) . "\n";
