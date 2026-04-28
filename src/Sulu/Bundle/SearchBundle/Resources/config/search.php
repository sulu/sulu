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

use Sulu\Bundle\SearchBundle\Controller\SearchController;
use Sulu\Bundle\SearchBundle\Controller\WebsiteSearchController;
use Sulu\Bundle\SearchBundle\Search\Configuration\IndexConfigurationProvider;
use Sulu\Bundle\SearchBundle\Search\Converter\StructureConverter;
use Sulu\Bundle\SearchBundle\Search\Factory;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_search.controller.search.class', SearchController::class);
    $parameters->set('sulu_search.search.factory.class', Factory::class);

    $services->set('sulu_search.controller.search', '%sulu_search.controller.search.class%')
        ->public()
        ->args([
            new Reference('massive_search.search_manager'),
            new Reference('massive_search.metadata.provider.chain'),
            new Reference('sulu_security.security_checker'),
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_core.list_rest_helper'),
            new Reference('sulu_search.index_configuration_provider'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_search.controller.website_search', WebsiteSearchController::class)
        ->public()
        ->args([
            new Reference('massive_search.search_manager'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_website.resolver.parameter'),
            new Reference('twig'),
            '%sulu_search.website.indexes%',
            new Reference('sulu_website.resolver.template_attribute'),
        ])
        ->tag('sulu.context', ['context' => 'website']);

    $services->set('sulu_search.index_configuration_provider', IndexConfigurationProvider::class)
        ->args([
            new Reference('translator'),
            '%sulu_search.indexes%',
        ]);

    $services->set('sulu_search.search.factory', '%sulu_search.search.factory.class%');

    $services->set(StructureConverter::class)
        ->args([
            new Reference('sulu_document_manager.document_manager'),
            new Reference('massive_search.search_manager'),
            new Reference('massive_search.object_to_document_converter'),
            new Reference('event_dispatcher'),
        ])
        ->tag('massive_search.converter', ['from' => 'structure']);
};
