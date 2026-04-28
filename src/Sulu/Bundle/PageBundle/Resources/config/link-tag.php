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

use Sulu\Bundle\PageBundle\Markup\Link\PageLinkProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.link_tag.page_provider', PageLinkProvider::class)
        ->args([
            new Reference('sulu_page.content_repository'),
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('request_stack'),
            new Reference('translator'),
            '%kernel.environment%',
            new Reference('sulu_security.access_control_manager'),
            new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.link.provider', ['alias' => 'page']);
};
