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

use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Symfony\Fixtures\LoadCustomUrlFixture;
use Sulu\CustomUrl\Infrastructure\Symfony\Normalizer\CustomUrlNormalizer;
use Sulu\CustomUrl\Infrastructure\Symfony\Router\CustomUrlRouter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(CustomUrlNormalizer::class)
        ->args([
            new Reference('serializer.normalizer.object'),
        ])
        ->tag('serializer.normalizer')
    ;

    $services->set(LoadCustomUrlFixture::class)
        ->args([
            new Reference(CustomUrlRepositoryInterface::class),
        ])
        ->tag('doctrine.fixture.orm')
    ;

    $services->set(CustomUrlRouter::class)
        ->args([
            service('sulu_custom_urls.repository'),
        ])
        ->tag('router', ['priority' => -30])
    ;
};
