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

use Sulu\Content\Application\ResourceLoader\Loader\LinkResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\RawResourceLoader;
use Sulu\Content\Application\ResourceLoader\Loader\TeaserResourceLoader;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.resource_loader_provider', ResourceLoaderProvider::class)
        ->args([tagged_iterator('sulu_content.resource_loader', indexAttribute: 'key', defaultIndexMethod: 'getKey')]);

    $services->set('sulu_content.link_resource_loader', LinkResourceLoader::class)
        ->args([new Reference('sulu_markup.link_tag.provider_pool')])
        ->tag('sulu_content.resource_loader', ['key' => 'link']);

    $services->set('sulu_content.teaser_resource_loader', TeaserResourceLoader::class)
        ->args([new Reference('sulu_admin.teaser_manager')])
        ->tag('sulu_content.resource_loader', ['key' => 'teaser']);

    $services->set('sulu_content.raw_resource_loader', RawResourceLoader::class)
        ->tag('sulu_content.resource_loader', ['key' => RawResourceLoader::RESOURCE_LOADER_KEY]);
};
