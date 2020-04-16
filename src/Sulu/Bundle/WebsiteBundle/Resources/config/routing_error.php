<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\HttpKernel\Controller\ErrorController;

return function (RoutingConfigurator $routes) {
    if (class_exists(ErrorController::class)) {
        $routes->import('@FrameworkBundle/Resources/config/routing/errors.xml')
            ->prefix('/_error');
    } else {
        $routes->import('@TwigBundle/Resources/config/routing/errors.xml')
            ->prefix('/_error');
    }
};
