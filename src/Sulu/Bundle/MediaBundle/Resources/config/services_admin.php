<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\ImageMapPropertyMetadataMapper;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\MediaSelectionPropertyMetadataMapper;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper\SingleMediaSelectionPropertyMetadataMapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    // PropertyMetadataMapper
    $services->set('sulu_media.type.media_selection_property_metadata_mapper', MediaSelectionPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.property_metadata_min_max_value_resolver')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'media_selection']);

    $services->set('sulu_media.type.single_media_selection_property_metadata_mapper', SingleMediaSelectionPropertyMetadataMapper::class)
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'single_media_selection']);

    $services->set('sulu_media.type.image_map_property_metadata_mapper', ImageMapPropertyMetadataMapper::class)
        ->args([new Reference('sulu_admin.schema_metadata_provider')])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'image_map']);
};
