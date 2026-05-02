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

use Sulu\Bundle\PageBundle\Controller\TeaserController;
use Sulu\Bundle\PageBundle\EventListener\TeaserSerializeEventSubscriber;
use Sulu\Bundle\PageBundle\Teaser\PageTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\PHPCRPageTeaserProvider;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPool;
use Sulu\Bundle\PageBundle\Teaser\TeaserContentType;
use Sulu\Bundle\PageBundle\Teaser\TeaserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_page.teaser.provider_pool', TeaserProviderPool::class)
        ->args([tagged_iterator('sulu.teaser.provider', indexAttribute: 'alias')]);

    $services->set('sulu_page.teaser.provider.content', PageTeaserProvider::class)
        ->args([
            new Reference('massive_search.search_manager'),
            new Reference('translator'),
            '%sulu_document_manager.show_drafts%',
            new Reference('sulu_page.teaser.provider.phpcr'),
        ])
        ->tag('sulu.teaser.provider', ['alias' => 'pages']);

    $services->set('sulu_page.teaser.provider.phpcr', PHPCRPageTeaserProvider::class)
        ->args([
            new Reference('sulu.content.query_executor'),
            new Reference('sulu_page.smart_content.data_provider.content.query_builder'),
            new Reference('sulu_page.structure.factory'),
            new Reference('translator'),
            '%sulu_document_manager.show_drafts%',
            '%sulu_security.permissions%',
        ]);

    $services->set('sulu_page.teaser.manager', TeaserManager::class)
        ->args([new Reference('sulu_page.teaser.provider_pool')]);

    $services->set('sulu_page.teaser.content_type', TeaserContentType::class)
        ->args([
            new Reference('sulu_page.teaser.provider_pool'),
            new Reference('sulu_page.teaser.manager'),
            new Reference('sulu_website.reference_store_pool'),
            new Reference('sulu_admin.property_metadata_min_max_value_resolver', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.content.type', ['alias' => 'teaser_selection'])
        ->tag('sulu.content.export', ['format' => '1.2.xliff', 'translate' => false])
        ->tag('sulu_admin.property_metadata_mapper', ['type' => 'teaser_selection']);

    $services->set('sulu_page.teaser.serializer.event_subscriber', TeaserSerializeEventSubscriber::class)
        ->args([new Reference('sulu_media.media_manager')])
        ->tag('jms_serializer.event_subscriber')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_page.teaser_controller', TeaserController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_page.teaser.manager'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);
};
