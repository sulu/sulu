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

use Sulu\Content\Application\ContentEnhancer\ContentEnhancer;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Application\ContentResolver\ContentResolver;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolver;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizer;
use Sulu\Content\Application\ContentResolver\ResolvableResourceLoader\ResolvableResourceLoader;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessor;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacer;
use Sulu\Content\Application\ContentResolver\Resolver\DimensionContentResolver;
use Sulu\Content\Application\ContentResolver\Resolver\ExcerptTaxonomyResolver;
use Sulu\Content\Application\ContentResolver\Resolver\RoutableTemplateResolver;
use Sulu\Content\Application\ContentResolver\Resolver\SeoResolver;
use Sulu\Content\Application\ContentResolver\Resolver\SettingsResolver;
use Sulu\Content\Application\ContentResolver\Resolver\TemplateResolver;
use Sulu\Content\Application\MetadataResolver\MetadataResolver;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\HiddenBlockVisitor;
use Sulu\Content\Application\PropertyResolver\BlockVisitor\ScheduleBlockVisitor;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProvider;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProviderInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\BlockPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\DatePropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\DateTimePropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\DefaultPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\LinkPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\SmartContentPropertyResolver;
use Sulu\Content\Application\PropertyResolver\Resolver\TeaserSelectionPropertyResolver;
use Sulu\Content\Application\SmartResolver\Resolver\SmartContentSmartResolver;
use Sulu\Content\Application\SmartResolver\SmartContentReferenceStore;
use Sulu\Content\Application\SmartResolver\SmartResolverProvider;
use Sulu\Content\Application\Visitor\ExcludeSelfSmartContentFiltersVisitor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_content.resolvable_resource_loader', ResolvableResourceLoader::class)
        ->args([
            new Reference('sulu_content.resource_loader_provider'),
            new Reference('sulu_content.smart_resolver_provider'),
        ]);

    $services->set('sulu_content.resolvable_resource_queue_processor', ResolvableResourceQueueProcessor::class);

    $services->set('sulu_content.resolvable_resource_replacer', ResolvableResourceReplacer::class)
        ->args([new Reference('sulu_http_cache.reference_store')]);

    $services->set('sulu_content.content_view_resolver', ContentViewResolver::class)
        ->args([
            new Reference('sulu_content.resolvable_resource_queue_processor'),
            tagged_iterator('sulu_content.content_resolver', indexAttribute: 'type'),
        ]);

    $services->set('sulu_content.content_view_data_normalizer', ContentViewDataNormalizer::class)
        ->args([new Reference('property_accessor')]);

    $services->set('sulu_content.content_enhancer', ContentEnhancer::class)
        ->args([tagged_iterator('sulu_content.dimension_content_enhancer')]);

    $services->alias(ContentEnhancerInterface::class, 'sulu_content.content_enhancer');

    $services->set('sulu_content.content_resolver', ContentResolver::class)
        ->public()
        ->args([
            new Reference('sulu_content.content_view_resolver'),
            new Reference('sulu_content.resolvable_resource_loader'),
            new Reference('sulu_content.resolvable_resource_queue_processor'),
            new Reference('sulu_content.resolvable_resource_replacer'),
            new Reference('sulu_content.content_view_data_normalizer'),
            new Reference('sulu_content.content_aggregator'),
            '',
            new Reference('sulu_content.content_enhancer'),
            new Reference('sulu_content.resource_loader_provider'),
        ]);

    $services->alias(ContentResolverInterface::class, 'sulu_content.content_resolver');

    $services->set('sulu_content.template_resolver', TemplateResolver::class)
        ->args([
            new Reference('sulu_admin.form_metadata_provider'),
            new Reference('sulu_content.metadata_resolver'),
        ]);

    $services->set('sulu_content.routable_template_resolver', RoutableTemplateResolver::class)
        ->decorate('sulu_content.template_resolver')
        ->args([
            new Reference('.inner'),
            new Reference('sulu_admin.form_metadata_provider'),
        ])
        ->tag('sulu_content.content_resolver', ['type' => 'template']);

    $services->set('sulu_content.settings_resolver', SettingsResolver::class)
        ->tag('sulu_content.content_resolver', ['type' => 'settings']);

    $services->set('sulu_content.excerpt_taxonomy_resolver', ExcerptTaxonomyResolver::class)
        ->args([
            new Reference('sulu_admin.form_metadata_provider'),
            new Reference('sulu_content.metadata_resolver'),
        ])
        ->tag('sulu_content.content_resolver', ['type' => 'excerpt']);

    $services->set('sulu_content.seo_resolver', SeoResolver::class)
        ->args([
            new Reference('sulu_admin.form_metadata_provider'),
            new Reference('sulu_content.metadata_resolver'),
        ])
        ->tag('sulu_content.content_resolver', ['type' => 'seo']);

    $services->set('sulu_content.dimension_content_resolver', DimensionContentResolver::class)
        ->args([new Reference('property_accessor')])
        ->tag('sulu_content.content_resolver', ['type' => 'object']);

    $services->set('sulu_content.metadata_resolver', MetadataResolver::class)
        ->args([new Reference('sulu_content.property_resolver_provider')]);

    $services->set('sulu_content.property_resolver_provider', PropertyResolverProvider::class)
        ->args([tagged_iterator('sulu_content.property_resolver', indexAttribute: 'type', defaultIndexMethod: 'getType')]);

    $services->alias(PropertyResolverProviderInterface::class, 'sulu_content.property_resolver_provider');

    $services->set('sulu_content.hidden_block_visitor', HiddenBlockVisitor::class)
        ->tag('sulu_content.block_visitor');

    $services->set('sulu_content.schedule_block_visitor', ScheduleBlockVisitor::class)
        ->args([
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('sulu_http_cache.cache_lifetime.request_store', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ])
        ->tag('sulu_content.block_visitor');

    $services->set('sulu_content.default_property_resolver', DefaultPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.block_property_resolver', BlockPropertyResolver::class)
        ->args([
            new Reference('logger'),
            new Reference('sulu_admin.metadata_provider_registry'),
            tagged_iterator('sulu_content.block_visitor', indexAttribute: 'type', defaultIndexMethod: 'getType'),
            '%kernel.debug%',
        ])
        ->call('setMetadataResolver', [new Reference('sulu_content.metadata_resolver')])
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.link_property_resolver', LinkPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.teaser_selection_property_resolver', TeaserSelectionPropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.date_property_resolver', DatePropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.datetime_property_resolver', DateTimePropertyResolver::class)
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.smart_content_property_resolver', SmartContentPropertyResolver::class)
        ->args([
            new Reference('request_stack'),
            tagged_iterator('sulu_content.smart_content_filters_visitor'),
        ])
        ->tag('sulu_content.property_resolver');

    $services->set('sulu_content.exclude_self_smart_content_filters_visitor', ExcludeSelfSmartContentFiltersVisitor::class)
        ->args([
            new Reference('request_stack'),
        ])
        ->tag('sulu_content.smart_content_filters_visitor');

    $services->set('sulu_content.smart_content_reference_store', SmartContentReferenceStore::class)
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('sulu_content.smart_resolver_provider', SmartResolverProvider::class)
        ->args([tagged_locator('sulu_content.smart_resolver', indexAttribute: 'type', defaultIndexMethod: 'getType')]);

    $services->set('sulu_content.smart_content_smart_resolver', SmartContentSmartResolver::class)
        ->args([
            tagged_locator('sulu_content.smart_content_provider', indexAttribute: 'type', defaultIndexMethod: 'getType'),
            new Reference('sulu_content.smart_content_reference_store'),
        ])
        ->tag('sulu_content.smart_resolver', ['type' => 'smart_content']);
};
