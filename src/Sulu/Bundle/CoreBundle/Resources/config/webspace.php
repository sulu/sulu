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

use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Url\WebspaceUrlChainProvider;
use Sulu\Component\Webspace\Url\WebspaceUrlProvider;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_core.webspace.cache_class', '%sulu.context%WebspaceCollectionCache');
    $parameters->set('sulu_core.webspace.base_class', 'WebspaceCollection');

    $services->set('sulu_core.webspace.loader.resolver', LoaderResolver::class)
        ->args([[new Reference('sulu_core.webspace.loader.xml.1.1'), service('sulu_core.webspace.loader.xml.1.0')]]);

    $services->set('sulu_core.webspace.loader.delegator', DelegatingLoader::class)
        ->args([new Reference('sulu_core.webspace.loader.resolver')]);

    $services->set('sulu_core.webspace.loader.xml.1.0', XmlFileLoader10::class)
        ->args([new Reference('file_locator'), null]);

    $services->set('sulu_core.webspace.loader.xml.1.1', XmlFileLoader11::class)
        ->args([new Reference('file_locator'), null, '%sulu.persistence.legacy_length%']);

    $services->set('sulu_core.webspace.webspace_manager.url_replacer', Replacer::class);

    $services->set('sulu_core.webspace.webspace_manager', WebspaceManager::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.loader.delegator'),
            new Reference('sulu_core.webspace.webspace_manager.url_replacer'),
            new Reference('request_stack'),
            ['config_dir' => '%sulu_core.webspace.config_dir%', 'cache_dir' => '%sulu.cache_dir%', 'debug' => '%kernel.debug%', 'cache_class' => '%sulu_core.webspace.cache_class%', 'base_class' => '%sulu_core.webspace.base_class%'],
            '%kernel.environment%',
            new Reference('router.request_context'),
            new Reference('sulu_admin.metadata_provider_registry'),
        ])
        ->tag('sulu.localization_provider');

    $services->alias(WebspaceManagerInterface::class, 'sulu_core.webspace.webspace_manager');

    $services->set('sulu_core.webspace.url_provider', WebspaceUrlChainProvider::class)
        ->args([tagged_iterator('sulu.webspace.url_provider')]);

    $services->set('sulu_core.webspace.url_provider.default', WebspaceUrlProvider::class)
        ->tag('sulu.webspace.url_provider');
};
