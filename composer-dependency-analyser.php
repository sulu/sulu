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

$config->addPathRegexesToExclude([
    '#/var/(cache|log)/#',
]);

$optionalIgnoreUnknownClasses = [];
$optionalIgnoreShadowDependencyExtensions = [];

// optional fallback to gd or vips
if (\extension_loaded('imagick')) {
    $optionalIgnoreShadowDependencyExtensions[] = 'ext-imagick';
} else {
    $optionalIgnoreUnknownClasses[] = 'Imagick';
}

return $config
    // SHADOW_DEPENDENCY
    ->ignoreErrorsOnExtensions(
        [
            ...$optionalIgnoreShadowDependencyExtensions,
            'ext-openssl', // fallbacks to random_bytes
            'ext-zip', // not required to run Sulu
            'ext-intl', // optional fallback to strcmp
            'ext-zend-opcache', // optional for cache invalidation
        ],
        [ErrorType::SHADOW_DEPENDENCY],
    )
    ->ignoreErrorsOnPackages(
        [
            'league/flysystem-local', // we support flysystem 3.0 which includes local so we can not require it directly
            // bc layer for lowest
        ],
        [ErrorType::SHADOW_DEPENDENCY],
    )
    // UnknownClasses
    ->ignoreUnknownClasses([
        ...$optionalIgnoreUnknownClasses,
        // bc layer for lowest
    ])
    // DEV_DEPENDENCY_IN_PROD: optional dependency
    ->ignoreErrorsOnPackages(
        [
            'php-ffmpeg/php-ffmpeg',
            'rokka/imagine-vips',
            'scheb/2fa-backup-code',
            'scheb/2fa-bundle',
            'scheb/2fa-email',
            'scheb/2fa-google-authenticator',
            'scheb/2fa-totp',
            'scheb/2fa-trusted-device',
            'symfony/emoji',
            'league/flysystem-memory', // false positiv eonly used in tests
            'symfony/monolog-bundle', // false positive only used in SuluTestKernel
            'coduo/php-matcher', // false positive only used in tests
        ],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    )
    // PROD_DEPENDENCY_ONLY_IN_DEV:
    ->ignoreErrorsOnPackages(
        [
            'symfony/yaml', // we use yaml configurations
        ],
        [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV],
    )
    // UNUSED_DEPENDENCY
    ->ignoreErrorsOnPackages(
        [
            'guzzlehttp/promises', // required for faster fos http cache clearing
            'nyholm/psr7', // required for faster fos http cache clearing
            'symfony/http-client', // required and used via symfony/http-client-contracts
            'symfony/css-selector', // kept for future usage
        ],
        [ErrorType::UNUSED_DEPENDENCY],
    )
;
