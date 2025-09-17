<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

require __DIR__ . '/vendor/symfony/dependency-injection/Loader/Configurator/ContainerConfigurator.php'; // see https://github.com/shipmonk-rnd/composer-dependency-analyser/issues/147#issuecomment-2202156380

$config = new Configuration();

return $config
    // UnknownClasses
    ->ignoreUnknownClasses([
        // for bc layers
    ])
    // SHADOW_DEPENDENCY
    ->ignoreErrorsOnExtension('ext-imagick', [ErrorType::SHADOW_DEPENDENCY]) // optional fallback to gd or vips
    ->ignoreErrorsOnExtension('ext-openssl', [ErrorType::SHADOW_DEPENDENCY]) // fallbacks to random_bytes
    ->ignoreErrorsOnExtension('ext-zip', [ErrorType::SHADOW_DEPENDENCY]) // not required to run Sulu
    ->ignoreErrorsOnExtension('ext-intl', [ErrorType::SHADOW_DEPENDENCY]) // optional fallback to strcmp
    // DEV_DEPENDENCY_IN_PROD: optional dependency
    ->ignoreErrorsOnPackage('php-ffmpeg/php-ffmpeg', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('rokka/imagine-vips', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-backup-code', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-bundle', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-email', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-google-authenticator', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-totp', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-trusted-device', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('symfony/emoji', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('league/flysystem-local', [ErrorType::SHADOW_DEPENDENCY]) // we support flysystem 3.0 which includes local so we can not require it directly
    ->ignoreErrorsOnPackage('coduo/php-matcher', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // false positive TestBundle requirement
    ->ignoreErrorsOnPackage('league/flysystem-memory', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // only for tests
    ->ignoreErrorsOnPackage('symfony/monolog-bundle', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // false positive only used in SuluTestKernel
    // UNUSED_DEPENDENCY
    ->ignoreErrorsOnPackage('guzzlehttp/promises', [ErrorType::UNUSED_DEPENDENCY]) // required for faster fos http cache clearing
    ->ignoreErrorsOnPackage('nyholm/psr7', [ErrorType::UNUSED_DEPENDENCY]) // required for faster fos http cache clearing
    ->ignoreErrorsOnPackage('symfony/asset', [ErrorType::UNUSED_DEPENDENCY]) // false positive we use assets
    ->ignoreErrorsOnPackage('symfony/css-selector', [ErrorType::UNUSED_DEPENDENCY]) // we use caches mostly via psr interfaces
    // PROD_DEPENDENCY_ONLY_IN_DEV
    ->ignoreErrorsOnPackage('symfony/yaml', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]) // we use yaml configurations
    ->ignoreErrorsOnPackage('symfony/cache', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]) // we use cache via psr/cache interface
;
