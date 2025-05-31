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

use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Symfony\Fixtures\LoadCustomUrlFixture;
use Sulu\CustomUrl\Infrastructure\Symfony\Serializer\CustomUrlNormalizer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
};
