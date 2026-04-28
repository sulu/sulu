<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\SearchBundle\Admin\SearchAdmin;
use Symfony\Component\DependencyInjection\Reference;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_search.admin', SearchAdmin::class)
        ->args([new Reference(ViewBuilderFactoryInterface::class)])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);
};
