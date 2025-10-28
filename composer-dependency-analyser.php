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
            'ext-iconv', // fallbacks to mbstring
            'ext-openssl', // fallbacks to random_bytes
            'ext-zip', // not required to run Sulu
            'ext-intl', // optional fallback to strcmp
        ],
        [ErrorType::SHADOW_DEPENDENCY],
    )
    ->ignoreErrorsOnPackages(
        [
            'guzzlehttp/guzzle', // bc layer replaced later by symfony/http-client
        ],
        [ErrorType::SHADOW_DEPENDENCY]
    )
    // UnknownClasses
    ->ignoreUnknownClasses([
        ...$optionalIgnoreUnknownClasses,
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
        'DTL\Bundle\PhpcrMigrations\PhpcrMigrationsBundle',
        'Symfony\Component\DependencyInjection\ContainerAwareInterface',
        'Symfony\Component\Emoji\EmojiTransliterator',
        'Symfony\Component\Security\Core\Security',
        'Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface',
    ])
    // DEV_DEPENDENCY_IN_PROD: optional dependency
    ->ignoreErrorsOnPackages(
        [
            'league/flysystem',
            'league/flysystem-aws-s3-v3',
            'league/flysystem-azure-blob-storage',
            'microsoft/azure-storage-blob',
            'php-ffmpeg/php-ffmpeg',
            'rokka/imagine-vips',
            'scheb/2fa-backup-code',
            'scheb/2fa-bundle',
            'scheb/2fa-email',
            'scheb/2fa-google-authenticator',
            'scheb/2fa-totp',
            'scheb/2fa-trusted-device',
            'superbalist/flysystem-google-storage',
            'symfony/stopwatch',
            'symfony/monolog-bundle', // false positive only used in SuluTestKernel
        ],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    )
    // UNUSED_DEPENDENCY
    ->ignoreErrorsOnPackages(
        [
            'doctrine/annotations',
            'guzzlehttp/promises', // required for faster fos http cache clearing
            'nyholm/psr7', // required for faster fos http cache clearing
            'symfony/css-selector', // kept for future usage
            'symfony/proxy-manager-bridge', // can only be removed when min symfony version is 6.2
            'symfony/yaml', // we use yaml configurations
        ],
        [ErrorType::UNUSED_DEPENDENCY],
    )
;
