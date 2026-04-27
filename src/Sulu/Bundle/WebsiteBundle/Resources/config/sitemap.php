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

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPool;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumper;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRenderer;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_website.sitemap.pool', SitemapProviderPool::class)
        ->args([tagged_iterator('sulu.sitemap.provider')])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_website.sitemap.xml_renderer', XmlSitemapRenderer::class)
        ->args([
            new Reference('sulu_website.sitemap.pool'),
            new Reference('twig'),
        ]);

    $services->set('sulu_website.sitemap.xml_dumper', XmlSitemapDumper::class)
        ->args([
            new Reference('sulu_website.sitemap.xml_renderer'),
            new Reference('sulu_website.sitemap.pool'),
            new Reference('filesystem'),
            '%sulu_website.sitemap.dump_dir%',
        ]);
};
