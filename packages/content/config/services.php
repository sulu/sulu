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

use Sulu\Content\Application\ContentAggregator\ContentAggregator;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentCopier\ContentCopier;
use Sulu\Content\Application\ContentCopier\ContentCopierInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapper;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Application\ContentHash\ContentHashChecker;
use Sulu\Content\Application\ContentManager\ContentManager;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentMerger\ContentMerger;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspector;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizer;
use Sulu\Content\Application\ContentNormalizer\ContentNormalizerInterface;
use Sulu\Content\Application\ContentPersister\ContentPersister;
use Sulu\Content\Application\ContentPersister\ContentPersisterInterface;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflow;
use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\ContentWorkflow\Subscriber\PublishTransitionSubscriber;
use Sulu\Content\Application\ContentWorkflow\Subscriber\RemoveDraftTransitionSubscriber;
use Sulu\Content\Application\ContentWorkflow\Subscriber\UnpublishTransitionSubscriber;
use Sulu\Content\Application\DimensionContentCollectionFactory\DimensionContentCollectionFactory;
use Sulu\Content\Domain\Factory\DimensionContentCollectionFactoryInterface;
use Sulu\Content\Infrastructure\Doctrine\CategoryFactory;
use Sulu\Content\Infrastructure\Doctrine\ContactFactory;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Infrastructure\Doctrine\MetadataLoader;
use Sulu\Content\Infrastructure\Doctrine\TagFactory;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactory;
use Sulu\Content\Infrastructure\Sulu\Admin\ContentViewBuilderFactoryInterface;
use Sulu\Content\Infrastructure\Sulu\HttpCache\EventSubscriber\DimensionContentTagSubscriber;
use Sulu\Content\Infrastructure\Sulu\Page\Select\WebspaceSelect;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.metadata_loader', MetadataLoader::class)
        ->args(['%kernel.bundles%'])
        ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'priority' => 100]);

    $services->set('sulu_content.content_view_builder_factory', ContentViewBuilderFactory::class)
        ->args([
            new Reference('sulu_admin.view_builder_factory'),
            new Reference('sulu_preview.preview_object_provider_registry'),
            new Reference('sulu_content.content_metadata_inspector'),
            new Reference('sulu_security.security_checker'),
            '%sulu_content.content_settings_forms%',
            '%sulu_content.content_excerpt_forms%',
            '%sulu_content.content_seo_forms%',
        ])
        ->tag('sulu.context', ['context' => 'admin']);

    $services->alias(ContentViewBuilderFactoryInterface::class, 'sulu_content.content_view_builder_factory');

    $services->set('sulu_content.webspace_select', WebspaceSelect::class)
        ->public()
        ->args([new Reference('sulu_core.webspace.webspace_manager')]);

    $services->set('sulu_content.content_merger', ContentMerger::class)
        ->args([
            tagged_iterator('sulu_content.merger'),
            new Reference('property_accessor'),
        ]);

    $services->alias(ContentMergerInterface::class, 'sulu_content.content_merger');

    $services->set('sulu_content.tag_factory', TagFactory::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.tag'),
        ]);

    $services->set('sulu_content.category_factory', CategoryFactory::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->set('sulu_content.contact_factory', ContactFactory::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->set('sulu_content.dimension_content_collection_factory', DimensionContentCollectionFactory::class)
        ->args([
            new Reference('sulu_content.content_metadata_inspector'),
            new Reference('sulu_content.content_data_mapper'),
            new Reference('property_accessor'),
        ]);

    $services->alias(DimensionContentCollectionFactoryInterface::class, 'sulu_content.dimension_content_collection_factory');

    $services->set('sulu_content.content_data_mapper', ContentDataMapper::class)
        ->args([tagged_iterator('sulu_content.data_mapper')]);

    $services->alias(ContentDataMapperInterface::class, 'sulu_content.content_data_mapper');

    $services->set('sulu_content.content_metadata_inspector', ContentMetadataInspector::class)
        ->args([new Reference('doctrine.orm.entity_manager')]);

    $services->alias(ContentMetadataInspectorInterface::class, 'sulu_content.content_metadata_inspector');

    $services->set('sulu_content.dimension_content_query_enhancer', DimensionContentQueryEnhancer::class);

    $services->alias(DimensionContentQueryEnhancer::class, 'sulu_content.dimension_content_query_enhancer');

    $services->set('sulu_content.content_persister', ContentPersister::class)
        ->args([
            new Reference('sulu_content.dimension_content_collection_factory'),
            new Reference('sulu_content.content_merger'),
        ]);

    $services->alias(ContentPersisterInterface::class, 'sulu_content.content_persister');

    $services->set('sulu_content.content_aggregator', ContentAggregator::class)
        ->args([
            new Reference('sulu_content.content_merger'),
            '%kernel.debug%',
        ]);

    $services->alias(ContentAggregatorInterface::class, 'sulu_content.content_aggregator');

    $services->set('sulu_content.content_normalizer', ContentNormalizer::class)
        ->args([tagged_iterator('sulu_content.normalizer')]);

    $services->alias(ContentNormalizerInterface::class, 'sulu_content.content_normalizer');

    $services->set('sulu_content.content_copier', ContentCopier::class)
        ->args([
            new Reference('sulu_content.content_aggregator'),
            new Reference('sulu_content.content_merger'),
            new Reference('sulu_content.content_persister'),
            new Reference('sulu_content.content_normalizer'),
        ]);

    $services->alias(ContentCopierInterface::class, 'sulu_content.content_copier');

    $services->set('sulu_content.content_workflow', ContentWorkflow::class)
        ->args([
            new Reference('sulu_content.content_metadata_inspector'),
            new Reference('sulu_content.content_merger'),
            new Reference('workflow.registry', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('event_dispatcher'),
            '%kernel.debug%',
        ]);

    $services->alias(ContentWorkflowInterface::class, 'sulu_content.content_workflow');

    $services->set('sulu_content.publish_transition_subscriber', PublishTransitionSubscriber::class)
        ->args([
            new Reference('sulu_content.content_copier'),
            new Reference('sulu_content.content_aggregator'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_content.remove_draft_transition_subscriber', RemoveDraftTransitionSubscriber::class)
        ->args([new Reference('sulu_content.content_copier')])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_content.unpublish_transition_subscriber', UnpublishTransitionSubscriber::class)
        ->args([
            new Reference('sulu_content.content_metadata_inspector'),
            new Reference('doctrine.orm.entity_manager'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_content.content_hash_checker', ContentHashChecker::class)
        ->args([new Reference('sulu_hash.auditable_hasher')]);

    $services->set('sulu_content.dimension_content_tag_subscriber', DimensionContentTagSubscriber::class)
        ->args([
            new Reference('sulu_http_cache.reference_store'),
            new Reference('request_stack'),
        ])
        ->tag('sulu.context', ['context' => 'website'])
        ->tag('kernel.event_subscriber');

    $services->set('sulu_content.content_manager', ContentManager::class)
        ->args([
            new Reference('sulu_content.content_aggregator'),
            new Reference('sulu_content.content_persister'),
            new Reference('sulu_content.content_normalizer'),
            new Reference('sulu_content.content_copier'),
            new Reference('sulu_content.content_workflow'),
        ]);

    $services->alias(ContentManagerInterface::class, 'sulu_content.content_manager');
};
