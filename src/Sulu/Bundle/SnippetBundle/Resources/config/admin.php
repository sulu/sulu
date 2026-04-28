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

use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\SnippetBundle\Admin\SnippetAdmin;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_snippet.admin', SnippetAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('sulu_core.webspace.webspace_manager'),
            '%sulu_snippet.content-type.default_enabled%',
            new Reference('sulu_activity.activity_list_view_builder_factory'),
            new Reference('sulu_reference.reference_list_view_builder_factory'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);
};
