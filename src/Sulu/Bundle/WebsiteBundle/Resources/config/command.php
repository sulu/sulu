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

use Sulu\Bundle\WebsiteBundle\Command\DumpSitemapCommand;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_website.command.dump_sitemap', DumpSitemapCommand::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_website.sitemap.xml_dumper'),
            new Reference('filesystem'),
            '%sulu_website.sitemap.dump_dir%',
            '%kernel.environment%',
            '%router.request_context.scheme%',
            '%router.request_context.host%',
        ])
        ->tag('console.command');
};
