<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactory;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\ListXmlLoader;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_core.list_builder.field_descriptor_factory', FieldDescriptorFactory::class)
        ->public()
        ->args([
            new Reference('sulu_core.list_builder.metadata.provider.list'),
            '%sulu_admin.lists.directories%',
            '%sulu.cache_dir%/field_descriptor',
            '%kernel.debug%',
        ])
        ->tag('kernel.cache_warmer', ['priority' => 1024]);

    $services->alias(FieldDescriptorFactoryInterface::class, 'sulu_core.list_builder.field_descriptor_factory');

    $services->set('sulu_core.list_builder.metadata.provider.list', ListXmlLoader::class)
        ->args([new Reference('sulu_core.list_builder.metadata.parameter_bag')]);

    $services->alias('sulu_core.list_builder.metadata.parameter_bag', 'parameter_bag');
};
