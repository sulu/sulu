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

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Contact\AccountFactory;
use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ContactBundle\Contact\ContactManager;
use Sulu\Bundle\ContactBundle\Controller\AccountController;
use Sulu\Bundle\ContactBundle\Controller\AccountMediaController;
use Sulu\Bundle\ContactBundle\Controller\ContactController;
use Sulu\Bundle\ContactBundle\Controller\ContactMediaController;
use Sulu\Bundle\ContactBundle\Controller\ContactTitleController;
use Sulu\Bundle\ContactBundle\Controller\PositionController;
use Sulu\Bundle\ContactBundle\DataFixtures\ORM\LoadDefaultTypes;
use Sulu\Bundle\ContactBundle\Entity\ContactTitle;
use Sulu\Bundle\ContactBundle\Entity\ContactTitleRepository;
use Sulu\Bundle\ContactBundle\Entity\Position;
use Sulu\Bundle\ContactBundle\Entity\PositionRepository;
use Sulu\Bundle\ContactBundle\EventListener\AccountListener;
use Sulu\Bundle\ContactBundle\EventListener\CacheInvalidationListener;
use Sulu\Bundle\ContactBundle\Provider\FormOfAddressProvider;
use Sulu\Bundle\ContactBundle\Twig\ContactTwigExtension;
use Sulu\Bundle\ContactBundle\Util\CustomerIdConverter;
use Sulu\Bundle\ContactBundle\Util\IndexComparator;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Component\Contact\SmartContent\AccountDataProvider;
use Sulu\Component\Contact\SmartContent\ContactDataProvider;
use Sulu\Component\Rest\ListBuilder\Filter\SelectFilterType;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();
    $parameters->set('sulu_contact.contact_title.entity', ContactTitle::class);
    $parameters->set('sulu_contact.position.entity', Position::class);

    $services->set('sulu_contact.admin', ContactAdmin::class)
        ->args([
            new Reference(ViewBuilderFactoryInterface::class),
            new Reference('sulu_security.security_checker'),
            new Reference('doctrine'),
        ])
        ->tag('sulu.admin')
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.contact_title_repository', ContactTitleRepository::class)
        ->args(['%sulu_contact.contact_title.entity%'])
        ->factory([new Reference('doctrine'), 'getRepository']);

    $services->set('sulu_contact.position_repository', PositionRepository::class)
        ->args(['%sulu_contact.position.entity%'])
        ->factory([new Reference('doctrine'), 'getRepository']);

    $services->set('sulu_contact.account_listener', AccountListener::class)
        ->tag('doctrine.event_listener', ['event' => 'postPersist']);

    $services->set('sulu_contact.account_manager', AccountManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_contact.account_factory'),
            new Reference('sulu.repository.account'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu.model.account.class%',
        ]);

    $services->set('sulu_contact.contact_manager', ContactManager::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_tag.tag_manager'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu.repository.account'),
            new Reference('sulu_contact.contact_title_repository'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_activity.domain_event_collector'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);

    $services->set('sulu_contact.twig.cache_adapter', ArrayAdapter::class);

    $services->set('sulu_contact.twig.cache', Cache::class)
        ->args([new Reference('sulu_contact.twig.cache_adapter')])
        ->factory([DoctrineProvider::class, 'wrap']);

    $services->set('sulu_contact.twig', ContactTwigExtension::class)
        ->args([
            new Reference('sulu_contact.twig.cache'),
            new Reference('sulu.repository.contact'),
        ])
        ->tag('twig.extension');

    $services->set('sulu_contact.account_factory', AccountFactory::class)
        ->public()
        ->args(['%sulu.model.account.class%']);

    $services->set('sulu_contact.smart_content.data_provider.contact', ContactDataProvider::class)
        ->args([
            new Reference('sulu_contact.contact_manager'),
            new Reference('sulu_core.array_serializer'),
            new Reference('sulu_contact.reference_store.contact'),
        ])
        ->tag('sulu.smart_content.data_provider', ['alias' => 'contacts']);

    $services->set('sulu_contact.smart_content.data_provider.account', AccountDataProvider::class)
        ->args([
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_core.array_serializer'),
            new Reference('sulu_contact.reference_store.account'),
        ])
        ->tag('sulu.smart_content.data_provider', ['alias' => 'accounts']);

    $services->set('sulu_contact.util.index_comparator', IndexComparator::class)
        ->public();

    $services->set('sulu_contact.util.id_converter', CustomerIdConverter::class)
        ->private();

    $services->set('sulu_contact.reference_store.contact', ReferenceStore::class)
        ->tag('sulu_website.reference_store', ['alias' => 'contact'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_contact.reference_store.account', ReferenceStore::class)
        ->tag('sulu_website.reference_store', ['alias' => 'account'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_contact.doctrine.invalidation_listener', CacheInvalidationListener::class)
        ->args([new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE)])
        ->tag('doctrine.event_listener', ['event' => 'postPersist'])
        ->tag('doctrine.event_listener', ['event' => 'postUpdate'])
        ->tag('doctrine.event_listener', ['event' => 'preRemove']);

    $services->set('sulu_contact.fixtures.default_types', LoadDefaultTypes::class)
        ->tag('doctrine.fixture.orm');

    $services->set('sulu_contact.account_controller', AccountController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu.repository.account'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_contact.account_factory'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu.model.account.class%',
            '%sulu.model.contact.class%',
            new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.account_media_controller', AccountMediaController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_contact.account_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu.model.account.class%',
            '%sulu.model.media.class%',
            new Reference('sulu_media.media_list_builder_factory'),
            new Reference('sulu_media.media_list_representation_factory'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.contact_controller', ContactController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('sulu_contact.contact_manager'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_contact.util.index_comparator'),
            '%sulu.model.contact.class%',
            '%sulu_security.system%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.contact_media_controller', ContactMediaController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('security.token_storage'),
            new Reference('sulu_core.doctrine_rest_helper'),
            new Reference('sulu_core.doctrine_list_builder_factory'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.media'),
            new Reference('sulu_media.media_manager'),
            new Reference('sulu_contact.contact_manager'),
            new Reference('sulu_activity.domain_event_collector'),
            '%sulu.model.contact.class%',
            '%sulu.model.media.class%',
            new Reference('sulu_media.media_list_builder_factory'),
            new Reference('sulu_media.media_list_representation_factory'),
            new Reference('sulu_core.list_builder.field_descriptor_factory'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.contact_title_controller', ContactTitleController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_contact.contact_title_repository'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.position_controller', PositionController::class)
        ->public()
        ->args([
            new Reference('fos_rest.view_handler'),
            new Reference('sulu_contact.position_repository'),
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu_activity.domain_event_collector'),
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->set('sulu_contact.country_filter_type', SelectFilterType::class)
        ->tag('sulu_core.list_builder_filter_type', ['alias' => 'country']);

    $services->set('sulu_contact.form_of_address_provider', FormOfAddressProvider::class)
        ->public()
        ->args([new Reference('translator')]);
};
