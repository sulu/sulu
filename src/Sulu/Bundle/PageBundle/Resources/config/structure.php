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

use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactory;
use Sulu\Component\Content\Metadata\Loader\StructureXmlLoader;
use Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser;
use Sulu\Component\Content\Metadata\Parser\SchemaXmlParser;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.structure.loader.xml', StructureXmlLoader::class)
        ->private()
        ->args([
            new Reference('sulu_http_cache.cache_lifetime.resolver'),
            new Reference('sulu_page.structure.properties_xml_parser'),
            new Reference('sulu_page.structure.schema_xml_parser'),
            new Reference('sulu.content.type_manager'),
            '%sulu.content.structure.required_properties%',
            '%sulu.content.structure.required_tags%',
            '%sulu_core.translated_locales%',
            new Reference('translator'),
        ]);

    $services->set('sulu_page.structure.properties_xml_parser', PropertiesXmlParser::class)
        ->private()
        ->args([
            new Reference('translator'),
            '%sulu_core.translated_locales%',
        ]);

    $services->set('sulu_page.structure.schema_xml_parser', SchemaXmlParser::class)
        ->private();

    $services->set('sulu_page.structure.factory', StructureMetadataFactory::class)
        ->public()
        ->args([
            new Reference('sulu_page.structure.loader.xml'),
            '%sulu.content.structure.paths%',
            '%sulu.content.structure.default_types%',
            '%kernel.cache_dir%/sulu/structures',
            '%kernel.debug%',
        ]);
};
