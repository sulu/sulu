<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Component\Finder\Finder;

$vendorDir = __DIR__ . '/../vendor';
$suluDir = __DIR__ . '/../src/Sulu';

require_once $vendorDir . '/autoload.php';

function extract_packages($packageList, $list, $nativePackageNames)
{
    foreach ($packageList as $key => $value) {
        if (isset($nativePackageNames[$key])) {
            continue;
        }

        $list[$key] = $value;
    }

    return $list;
}

$finder = new Finder();
$finder->name('composer.json')->in($suluDir);

$require = [];
$requireDev = [];
$suggest = [];
$replace = [];

$packages = [];
$nativePackageNames = [
    'sulu/sulu' => 'sulu/sulu',
];

foreach ($finder as $file) {
    $json = file_get_contents($file);
    $package = json_decode($json, true);
    $packages[] = $package;
    $nativePackageNames[$package['name']] = $package['name'];
    $replace[$package['name']] = 'self.version';
}

foreach ($packages as $package) {
    if (isset($package['require'])) {
        $require = extract_packages($package['require'], $require, $nativePackageNames);
    }

    if (isset($package['require-dev'])) {
        $requireDev = extract_packages($package['require-dev'], $requireDev, $nativePackageNames);
    }

    if (isset($package['suggest'])) {
        $suggest = extract_packages($package['suggest'], $requireDev, $nativePackageNames);
    }
}

ksort($require);
ksort($requireDev);

$newPackage = [
    'name' => 'sulu/sulu',
    'type' => 'library',
    'description' => 'SuluCMF core distribution',
    'license' => 'MIT',
    'keywords' => ['core', 'sulu'],
    'version' => 'dev-develop',
    'authors' => [
        [
            'name' => 'Sulu Community',
            'homepage' => 'https://github.com/sulu-cmf/sulu/contributors',
        ],
    ],
    'require' => $require,
    'require-dev' => $requireDev,
    'replace' => $replace,
    'suggest' => $suggest,
    'autoload' => [
        'psr-0' => [
            'Sulu\\' => 'src/',
        ],
    ],
];

$newPackage = json_encode($newPackage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
die($newPackage);
