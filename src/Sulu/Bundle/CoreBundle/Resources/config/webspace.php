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

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_core.webspace.cache_class', '%sulu.context%WebspaceCollectionCache');
    $parameters->set('sulu_core.webspace.base_class', 'WebspaceCollection');

    $services->set('sulu_core.webspace.loader.resolver', \Symfony\Component\Config\Loader\LoaderResolver::class)
        ->args([[new Reference('sulu_core.webspace.loader.xml.1.1'), service('sulu_core.webspace.loader.xml.1.0')]]);

    $services->set('sulu_core.webspace.loader.delegator', \Symfony\Component\Config\Loader\DelegatingLoader::class)
        ->args([new Reference('sulu_core.webspace.loader.resolver')]);

    $services->set('sulu_core.webspace.loader.xml.1.0', \Sulu\Component\Webspace\Loader\XmlFileLoader10::class)
        ->args([new Reference('file_locator')]);

    $services->set('sulu_core.webspace.loader.xml.1.1', \Sulu\Component\Webspace\Loader\XmlFileLoader11::class)
        ->args([new Reference('file_locator')]);

    $services->set('sulu_core.webspace.webspace_manager.url_replacer', \Sulu\Component\Webspace\Url\Replacer::class);

    $services->set('sulu_core.webspace.webspace_manager', \Sulu\Component\Webspace\Manager\WebspaceManager::class)
        ->public()
        ->args([
            new Reference('sulu_core.webspace.loader.delegator'),
            new Reference('sulu_core.webspace.webspace_manager.url_replacer'),
            new Reference('request_stack'),
            ['config_dir' => '%sulu_core.webspace.config_dir%', 'cache_dir' => '%sulu.cache_dir%', 'debug' => '%kernel.debug%', 'cache_class' => '%sulu_core.webspace.cache_class%', 'base_class' => '%sulu_core.webspace.base_class%'],
            '%kernel.environment%',
            '%router.request_context.host%',
            '%router.request_context.scheme%',
            new Reference('sulu_page.structure.factory'),
        ])
        ->tag('sulu.localization_provider');

    $services->alias(\Sulu\Component\Webspace\Manager\WebspaceManagerInterface::class, 'sulu_core.webspace.webspace_manager');

    $services->set('sulu_core.webspace.document_manager.webspace_initializer', \Sulu\Component\Webspace\Document\Initializer\WebspaceInitializer::class)
        ->args([
            new Reference('sulu_core.webspace.webspace_manager'),
            new Reference('sulu_document_manager.document_manager'),
            new Reference('sulu_document_manager.document_inspector'),
            new Reference('sulu_document_manager.path_builder'),
            new Reference('sulu_document_manager.node_manager'),
        ])
        ->tag('sulu_document_manager.initializer', ['priority' => 0]);

    $services->set('sulu_core.webspace.settings_manager', \Sulu\Component\Webspace\Settings\SettingsManager::class)
        ->args([
            new Reference('sulu_document_manager.session_manager'),
            new Reference('sulu.phpcr.session'),
        ]);

    $services->set('sulu_core.webspace.url_provider', \Sulu\Component\Webspace\Url\WebspaceUrlChainProvider::class)
        ->args([tagged_iterator('sulu.webspace.url_provider')]);

    $services->set('sulu_core.webspace.url_provider.default', \Sulu\Component\Webspace\Url\WebspaceUrlProvider::class)
        ->tag('sulu.webspace.url_provider');
};
