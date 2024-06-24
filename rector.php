<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Rector\Config\RectorConfig;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withRootFiles()
    ->withPaths([
        __DIR__ . '/bin',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkipPath('*/var/cache')
    ->withSkipPath('*/tests/Resources/cache')
    ->withSkipPath('*/node_modules')
    // ->withImportNames(importShortClasses: false)
    ->withSets([
        // SetList::CODE_QUALITY,
        // LevelSetList::UP_TO_PHP_80,
    ])
    ->withRules([
        StaticDataProviderClassMethodRector::class, // prepare for PHPUnit >= 10
    ]);
