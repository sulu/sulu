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

$config = new Configuration();

return $config
    // SHADOW_DEPENDENCY
    ->ignoreErrorsOnExtension('ext-iconv', [ErrorType::SHADOW_DEPENDENCY]) // fallbacks to mbstring
    ->ignoreErrorsOnExtension('ext-imagick', [ErrorType::SHADOW_DEPENDENCY]) // optional fallback to gd or vips
    ->ignoreErrorsOnExtension('ext-openssl', [ErrorType::SHADOW_DEPENDENCY]) // fallbacks to random_bytes
    ->ignoreErrorsOnExtension('ext-zip', [ErrorType::SHADOW_DEPENDENCY]) // not required to run Sulu
    ->ignoreErrorsOnPackage('guzzlehttp/guzzle', [ErrorType::SHADOW_DEPENDENCY]) // bc layer replaced later by symfony/http-client
    // UnknownClasses
    ->ignoreUnknownClasses([
        // bc layer for lowest
        'FOS\RestBundle\Controller\FOSRestController',
        'Swift_Events_SendEvent',
        'Swift_Events_SendListener',
        'Swift_Mailer',
        'Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser',
        'Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle',
        'Symfony\Bundle\TwigBundle\Controller\ExceptionController',
        'Symfony\Bundle\SecurityBundle\Command\UserPasswordEncoderCommand',
        'Symfony\Component\Security\Core\Authentication\Token\AnonymousToken',
        'Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface',
        'Symfony\Component\Security\Core\Event\AuthenticationFailureEvent',
        'Symfony\Component\Security\Core\Exception\UsernameNotFoundException',
        'Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface',
    ])
    // DEV_DEPENDENCY_IN_PROD: optional dependency
    ->ignoreErrorsOnPackage('league/flysystem', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('league/flysystem-aws-s3-v3', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('league/flysystem-azure-blob-storage', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('microsoft/azure-storage-blob', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('php-ffmpeg/php-ffmpeg', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('rokka/imagine-vips', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-backup-code', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-bundle', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-email', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-google-authenticator', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-totp', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('scheb/2fa-trusted-device', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('superbalist/flysystem-google-storage', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('symfony/stopwatch', [ErrorType::DEV_DEPENDENCY_IN_PROD])
    ->ignoreErrorsOnPackage('symfony/monolog-bundle', [ErrorType::DEV_DEPENDENCY_IN_PROD]) // false positive only used in SuluTestKernel
    // UNUSED_DEPENDENCY
    ->ignoreErrorsOnPackage('doctrine/annotations', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('guzzlehttp/promises', [ErrorType::UNUSED_DEPENDENCY]) // required for faster fos http cache clearing
    ->ignoreErrorsOnPackage('nyholm/psr7', [ErrorType::UNUSED_DEPENDENCY]) // required for faster fos http cache clearing
    ->ignoreErrorsOnPackage('symfony/asset', [ErrorType::UNUSED_DEPENDENCY]) // false positive we use assets
    ->ignoreErrorsOnPackage('symfony/cache', [ErrorType::UNUSED_DEPENDENCY]) // we use caches mostly via psr interfaces
    ->ignoreErrorsOnPackage('symfony/css-selector', [ErrorType::UNUSED_DEPENDENCY]) // we use caches mostly via psr interfaces
    ->ignoreErrorsOnPackage('symfony/proxy-manager-bridge', [ErrorType::UNUSED_DEPENDENCY]) // can only be removed when min symfony version is 6.2
    ->ignoreErrorsOnPackage('symfony/yaml', [ErrorType::UNUSED_DEPENDENCY]) // we use yaml configurations
;
