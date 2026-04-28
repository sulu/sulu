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

use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\CollectionSelectionPropertyResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\ImageMapPropertyResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\MediaSelectionPropertyResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleCollectionSelectionPropertyResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\PropertyResolver\SingleMediaSelectionPropertyResolver;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\CollectionResourceLoader;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Content\ResourceLoader\MediaResourceLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('sulu_media.media_selection_property_resolver', MediaSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_media.single_media_selection_property_resolver', SingleMediaSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_media.image_map_property_resolver', ImageMapPropertyResolver::class)
        ->args([
            new Reference('logger'),
            new Reference('sulu_admin.metadata_provider_registry'),
            '%kernel.debug%',
        ])
        ->call('setMetadataResolver', [new Reference('sulu_content.metadata_resolver')])
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_media.collection_selection_property_resolver', CollectionSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_media.single_collection_selection_property_resolver', SingleCollectionSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_media.media_resource_loader', MediaResourceLoader::class)
        ->args([
            new Reference('sulu_media.media_manager'),
            new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            '%sulu_security.permissions%',
            new Reference('sulu_core.webspace.request_analyzer'),
        ])
        ->tag('sulu_content.resource_loader', ['key' => 'media']);

    $services->set('sulu_media.collection_resource_loader', CollectionResourceLoader::class)
        ->args([new Reference('sulu_media.collection_manager')])
        ->tag('sulu_content.resource_loader', ['key' => 'collection']);
};
