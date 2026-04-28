<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_search.metadata.driver.structure.class', 'Sulu\Bundle\SearchBundle\Search\Metadata\StructureDriver');

    $services->set('sulu_search.metadata.driver.structure', '%sulu_search.metadata.driver.structure.class%')
        ->args([
            new Reference('massive_search.factory'),
            new Reference('sulu_document_manager.metadata_factory'),
            new Reference('sulu_page.structure.factory'),
        ])
        ->tag('massive_search.metadata.driver');
};
