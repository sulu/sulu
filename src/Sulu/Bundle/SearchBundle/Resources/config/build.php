<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\SearchBundle\Build\IndexBuilder;
use Sulu\Bundle\SearchBundle\Build\InitBuilder;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_search.build.index.class', IndexBuilder::class);
    $parameters->set('sulu_search.build.init.class', InitBuilder::class);

    $services->set('sulu_search.build.index', '%sulu_search.build.index.class%')
        ->tag('massive_build.builder');

    $services->set('sulu_search.build.init', '%sulu_search.build.init.class%')
        ->tag('massive_build.builder');
};
