<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\SecurityBundle\SingleSignOn\Adapter\OpenId\OpenIdSingleSignOnAdapterFactory;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterFactory;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnAdapterProvider;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnLoginRequestSubscriber;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnTokenExtractor;
use Sulu\Bundle\SecurityBundle\SingleSignOn\SingleSignOnTokenHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_security.open_id_login_subscriber', SingleSignOnLoginRequestSubscriber::class)
        ->args([
            new Reference('sulu_security.single_sign_provider'),
            new Reference('router'),
            new Reference('sulu.repository.user'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_security.single_sign_on_adapter_factory_open_id', OpenIdSingleSignOnAdapterFactory::class)
        ->args([
            new Reference('http_client'),
            new Reference('sulu_security.user_repository'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.repository.role'),
            new Reference('router'),
            '%sulu_core.translations%',
        ])
        ->tag('sulu_security.single_sign_on_factory');

    $services->set('sulu_security.single_sign_on_adapter_factory', SingleSignOnAdapterFactory::class)
        ->args([tagged_iterator('sulu_security.single_sign_on_factory')]);

    $services->set('sulu_security.single_sign_provider', SingleSignOnAdapterProvider::class)
        ->args([tagged_locator('sulu_security.single_sign_on_adapter', indexAttribute: 'domain')]);

    $services->set('sulu_security.single_sign_on_token_extractor', SingleSignOnTokenExtractor::class)
        ->args([new Reference('sulu_security.single_sign_provider')]);

    $services->set('sulu_security.single_sign_on_token_handler', SingleSignOnTokenHandler::class)
        ->args([
            new Reference('sulu_security.single_sign_provider'),
            new Reference('http_client'),
        ]);
};
