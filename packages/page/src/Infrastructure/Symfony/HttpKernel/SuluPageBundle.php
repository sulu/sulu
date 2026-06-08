<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Symfony\HttpKernel;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Sulu\Page\Application\Mapper\PageContentMapper;
use Sulu\Page\Application\Mapper\PageMapperInterface;
use Sulu\Page\Application\MessageHandler\ApplyWorkflowTransitionPageMessageHandler;
use Sulu\Page\Application\MessageHandler\CopyLocalePageMessageHandler;
use Sulu\Page\Application\MessageHandler\CopyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Application\MessageHandler\ModifyPageMessageHandler;
use Sulu\Page\Application\MessageHandler\MovePageMessageHandler;
use Sulu\Page\Application\MessageHandler\OrderPageMessageHandler;
use Sulu\Page\Application\MessageHandler\RemovePageMessageHandler;
use Sulu\Page\Application\MessageHandler\RemovePageTranslationMessageHandler;
use Sulu\Page\Application\MessageHandler\RestorePageVersionMessageHandler;
use Sulu\Page\Domain\Event\PageCreatedEvent;
use Sulu\Page\Domain\Event\PageModifiedEvent;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageRestoredEvent;
use Sulu\Page\Domain\Event\PageTranslationAddedEvent;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Event\PageTranslationRestoredEvent;
use Sulu\Page\Domain\Event\PageWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Doctrine\Hydrator\SafeTreeObjectHydrator;
use Sulu\Page\Infrastructure\Doctrine\Repository\NavigationRepository;
use Sulu\Page\Infrastructure\Doctrine\Repository\PageRepository;
use Sulu\Page\Infrastructure\JMS\Serializer\WebspaceSerializeEventSubscriber;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\BlockSettingsFormMetadataVisitor;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\WebspaceRouteModeTypedFormMetadataVisitor;
use Sulu\Page\Infrastructure\Sulu\Admin\MetadataVisitor\WebspaceTypedFormMetadataVisitor;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Admin\PropertyMetadataMapper\PageTreeRoutePropertyMetadataMapper;
use Sulu\Page\Infrastructure\Sulu\Build\HomepageBuilder;
use Sulu\Page\Infrastructure\Sulu\Content\ContentResolver\PageLinkDimensionContentEnhancer;
use Sulu\Page\Infrastructure\Sulu\Content\DataMapper\NavigationContextDataMapper;
use Sulu\Page\Infrastructure\Sulu\Content\Merger\NavigationContextMerger;
use Sulu\Page\Infrastructure\Sulu\Content\Normalizer\PageExcerptNormalizer;
use Sulu\Page\Infrastructure\Sulu\Content\Normalizer\PageNormalizer;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PageSmartContentProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PageTeaserProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor\SegmentBlockVisitor;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageTreeRoutePropertyResolver;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\SinglePageSelectionPropertyResolver;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Sulu\Page\Infrastructure\Sulu\Content\Visitor\SegmentSmartContentFiltersVisitor;
use Sulu\Page\Infrastructure\Sulu\Content\Visitor\WebspaceSmartContentFiltersVisitor;
use Sulu\Page\Infrastructure\Sulu\HttpCache\EventSubscriber\PageCacheInvalidationSubscriber;
use Sulu\Page\Infrastructure\Sulu\Reference\PageReferenceRefresher;
use Sulu\Page\Infrastructure\Sulu\Route\PageRouteDefaultsProvider;
use Sulu\Page\Infrastructure\Sulu\Route\PageWebspaceRouteGenerator;
use Sulu\Page\Infrastructure\Sulu\Search\AdminPageIndexListener;
use Sulu\Page\Infrastructure\Sulu\Search\AdminPageReindexProvider;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\AdminPageReindexProviderEnhancerInterface;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexContentEnhancer;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexExcerptEnhancer;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexProviderEnhancerInterface;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexTaxonomyEnhancer;
use Sulu\Page\Infrastructure\Sulu\Search\WebsitePageIndexListener;
use Sulu\Page\Infrastructure\Sulu\Search\WebsitePageReindexProvider;
use Sulu\Page\Infrastructure\Sulu\Security\PageDescendantSecurityListener;
use Sulu\Page\Infrastructure\Sulu\Security\PageSecurityListener;
use Sulu\Page\Infrastructure\Sulu\Sitemap\PagesSitemapProvider;
use Sulu\Page\Infrastructure\Sulu\Trash\PageTrashItemHandler;
use Sulu\Page\Infrastructure\Sulu\Webspace\PageSegmentListener;
use Sulu\Page\Infrastructure\Symfony\DependencyInjection\Compiler\OverrideTreeListenerPass;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\ContentPathTwigExtension;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\PageTwigExtension;
use Sulu\Page\UserInterface\Command\InitializeHomepageCommand;
use Sulu\Page\UserInterface\Controller\Admin\PageController;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluPageBundle extends AbstractBundle
{
    use PersistenceBundleTrait;
    use PersistenceExtensionTrait;

    public function __construct()
    {
        $this->name = 'SuluPageBundle';
        $this->extensionAlias = 'sulu_page';
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Page::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('page_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(PageDimensionContent::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder); // @phpstan-ignore-line

        $services = $container->services();

        // Define autoconfigure interfaces for mappers
        $builder->registerForAutoconfiguration(PageMapperInterface::class)
            ->addTag('sulu_page.page_mapper');

        // Message Handler services
        $services->set('sulu_page.create_page_handler')
            ->class(CreatePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                tagged_iterator('sulu_page.page_mapper'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.modify_page_handler')
            ->class(ModifyPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                tagged_iterator('sulu_page.page_mapper'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_content.content_hash_checker'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.remove_page_handler')
            ->class(RemovePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.remove_page_translation_handler')
            ->class(RemovePageTranslationMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.apply_workflow_transition_page_handler')
            ->class(ApplyWorkflowTransitionPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_workflow'),
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.copy_locale_page_handler')
            ->class(CopyLocalePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.order_page_handler')
            ->class(OrderPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.move_page_handler')
            ->class(MovePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.copy_page_handler')
            ->class(CopyPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu.core.localization_manager'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.restore_page_version_handler')
            ->class(RestorePageVersionMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        // Mapper service
        $services->set('sulu_page.page_content_mapper')
            ->class(PageContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_page.page_mapper');

        // DataMapper service
        $services->set('sulu_page.navigation_context_data_mapper')
            ->class(NavigationContextDataMapper::class)
            ->tag('sulu_content.data_mapper');

        // Merger service
        $services->set('sulu_page.navigation_context_merger')
            ->class(NavigationContextMerger::class)
            ->tag('sulu_content.merger');

        // Normalizer services
        $services->set('sulu_page.page_normalizer')
            ->class(PageNormalizer::class)
            ->tag('sulu_content.normalizer');

        $services->set('sulu_page.page_excerpt_normalizer')
            ->class(PageExcerptNormalizer::class)
            ->tag('sulu_content.normalizer');

        // Dimension Content Enhancer service
        $services->set('sulu_page.page_link_dimension_content_enhancer')
            ->class(PageLinkDimensionContentEnhancer::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_markup.link_tag.provider_pool'),
                new Reference('sulu_route.route_repository'),
            ])
            ->tag('sulu_content.dimension_content_enhancer');

        // Property Metadata Mapper services
        $services->set('sulu_page.page_tree_route_property_metadata_mapper')
            ->class(PageTreeRoutePropertyMetadataMapper::class)
            ->tag('sulu_admin.property_metadata_mapper', ['type' => 'page_tree_route']);

        // Property Resolver services
        $services->set('sulu_page.page_selection_property_resolver')
            ->class(PageSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_page.single_page_selection_property_resolver')
            ->class(SinglePageSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_page.page_tree_route_property_resolver')
            ->class(PageTreeRoutePropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_page.segment_block_visitor')
            ->class(SegmentBlockVisitor::class)
            ->args([
                new Reference('sulu_core.webspace.request_analyzer'),
            ])
            ->tag('sulu_content.block_visitor');

        // Resource Loader services
        $services->set('sulu_page.page_resource_loader')
            ->class(PageResourceLoader::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                param('sulu_security.permissions'),
                new Reference('sulu_core.webspace.request_analyzer'),
            ])
            ->tag('sulu_content.resource_loader', ['type' => PageResourceLoader::RESOURCE_LOADER_KEY]);

        // Sulu Builder service
        $services->set('sulu_page.homepage_builder')
            ->class(HomepageBuilder::class)
            ->tag('massive_build.builder');

        // Sulu Integration service
        $services->set('sulu_page.page_admin')
            ->class(PageAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_activity.activity_list_view_builder_factory'),
                new Reference('sulu_admin.teaser_provider_pool'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Route Integration
        $services->set('sulu_page.page_webspace_route_generator')
            ->class(PageWebspaceRouteGenerator::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('request_stack'),
            ])
            ->tag('sulu_route.webspace_route_generator', ['webspace' => '.default']);

        $services->set('sulu_page.webspace_route_mode_typed_form_metadata_visitor')
            ->class(WebspaceRouteModeTypedFormMetadataVisitor::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('sulu_admin.typed_form_metadata_visitor');

        $services->set('sulu_page.default_template_typed_form_metadata_visitor')
            ->class(WebspaceTypedFormMetadataVisitor::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('sulu_admin.typed_form_metadata_visitor');

        $services->set('sulu_page.block_settings_form_metadata_visitor')
            ->class(BlockSettingsFormMetadataVisitor::class)
            ->args([
                new Reference('sulu_admin.xml_form_metadata_loader'),
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('sulu_admin.form_metadata_visitor');

        // Repositories services
        $services->set('sulu_page.page_repository')
            ->class(PageRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_security.access_control_query_enhancer'),
            ])
            ->tag('sulu_security.access_control_descendant_provider');

        $services->alias(PageRepositoryInterface::class, 'sulu_page.page_repository');
        $services->alias(PageRepository::class, 'sulu_page.page_repository');

        // Commands services
        $services->set('sulu_page.command.initialize_homepage')
            ->class(InitializeHomepageCommand::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_message_bus'),
            ])
            ->tag('console.command');

        // Controllers services
        $services->set('sulu_page.admin_page_controller')
            ->class(PageController::class)
            ->public()
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_message_bus'),
                new Reference('serializer'),
                // additional services to be removed when no longer needed
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
                new Reference('sulu_security.access_control_manager'),
                new Reference('security.token_storage'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_security.security_checker'),
                param('sulu_core.is_single_locale'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        // Preview service
        $services->set('sulu_page.page_preview_provider')
            ->class(ContentObjectProvider::class)
            ->args([
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_data_mapper'),
                '%sulu.model.page.class%',
                null, // TODO add security context for preview
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu_preview.object_provider', ['provider-key' => 'pages']);

        $services->set('sulu_page.page_route_defaults_provider')
            ->class(PageRouteDefaultsProvider::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('sulu_http_cache.cache_lifetime.resolver'),
                new Reference('sulu_markup.link_tag.provider_pool'),
                expr("container.hasParameter('sulu_audience_targeting.enabled')"),
            ])
            ->tag('sulu_route.route_defaults_provider', ['resource_key' => 'pages']);

        // Content services
        $services->set('sulu_page.page_teaser_provider')
            ->class(PageTeaserProvider::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_enhancer'),
                new Reference('translator'),
                new Reference('sulu_admin.teaser_tag_property_extractor'),
            ])
            ->tag('sulu.teaser.provider', ['alias' => PageInterface::RESOURCE_KEY]);

        $services->set('sulu_page.page_link_provider')
            ->class(PageLinkProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_route.route_generator'),
                new Reference('sulu_http_cache.reference_store'),
                new Reference('translator'),
                '%sulu.model.page_content.class%',
            ])
            ->tag('sulu.link.provider', ['alias' => PageLinkProvider::ALIAS]);

        // Smart Content services
        $services->set('sulu_page.page_smart_content_provider')
            ->class(PageSmartContentProvider::class)
            ->args([
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_admin.form_metadata_provider'),
                new Reference('sulu_admin.smart_content_query_enhancer'),
                new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('doctrine.orm.entity_manager'),
                param('kernel.bundles'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_security.access_control_query_enhancer'),
                new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                param('sulu_security.permissions'),
            ])
            ->tag('sulu_content.smart_content_provider', ['type' => PageInterface::RESOURCE_KEY]);

        $services->set('sulu_page.webspace_smart_content_filters_visitor_webspace')
            ->class(WebspaceSmartContentFiltersVisitor::class)
            ->args([
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('request_stack'),
            ])
            ->tag('sulu_content.smart_content_filters_visitor');

        $services->set('sulu_page.page_smart_content_filters_visitor')
            ->class(SegmentSmartContentFiltersVisitor::class)
            ->args([
                new Reference('sulu_core.webspace.request_analyzer'),
            ])
            ->tag('sulu_content.smart_content_filters_visitor');

        // Navigation
        $services->set('sulu_page.navigation_repository')
            ->class(NavigationRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_resolver'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_security.access_control_query_enhancer'),
                new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                param('sulu_security.permissions'),
                expr("container.hasParameter('sulu_audience_targeting.enabled')"),
            ]);

        $services->alias(NavigationRepository::class, 'sulu_page.navigation_repository');

        // Twig Extensions
        $services->set('sulu_page.navigation_twig_extension')
            ->class(NavigationTwigExtension::class)
            ->args([
                new Reference('sulu_page.navigation_repository'),
                new Reference('sulu_core.webspace.request_analyzer'),
            ])
            ->tag('twig.extension');

        $services->set('sulu_page.content_path_twig_extension')
            ->class(ContentPathTwigExtension::class)
            ->args([
                new Reference('sulu_route.route_generator'),
                new Reference('router.default'),
            ])
            ->tag('twig.extension');

        $services->set('sulu_page.page_twig_extension')
            ->class(PageTwigExtension::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('sulu_http_cache.reference_store'),
                new Reference('sulu_content.content_resolver'),
            ])
            ->tag('twig.extension');

        // Reference
        $services->set('sulu_page.page_reference_refresher')
            ->class(PageReferenceRefresher::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_reference.reference_repository'),
                new Reference('sulu_content.content_view_resolver'),
                new Reference('sulu_content.content_merger'),
            ])
            ->tag('sulu_reference.refresher'); // TODO add resource key?

        // Serialization
        $services->set('sulu_page.webspace.serializer.event_subscriber')
            ->class(WebspaceSerializeEventSubscriber::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_core.webspace.url_provider'),
                new Reference('sulu_security.access_control_manager'),
                new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Parameter('kernel.environment'),
            ])
            ->tag('jms_serializer.event_subscriber');

        // Sitemap
        $services->set('sulu_page.pages_sitemap_provider')
            ->class(PagesSitemapProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_core.webspace.webspace_manager'),
                '%kernel.environment%',
                new Reference('sulu_security.access_control_query_enhancer'),
                new Reference('security.helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                param('sulu_security.permissions'),
            ])
            ->tag('sulu.sitemap.provider');

        // Trash services
        /** @var array<string, class-string> $bundles */
        $bundles = $builder->getParameter('kernel.bundles');
        if (isset($bundles['SuluTrashBundle'])) {
            $services->set('sulu_page.trash_item_handler')
                ->class(PageTrashItemHandler::class)
                ->args([
                    new Reference('sulu_trash.trash_item_repository'),
                    new Reference('sulu_page.page_repository'),
                    new Reference('sulu_content.content_normalizer'),
                    new Reference('sulu_content.content_merger'),
                    tagged_iterator('sulu_page.page_mapper'),
                    new Reference('sulu_activity.domain_event_collector'),
                ])
                ->tag('sulu_trash.store_trash_item_handler')
                ->tag('sulu_trash.restore_trash_item_handler')
                ->tag('sulu_trash.restore_configuration_provider');
        }

        $services->set('sulu_page.admin_page_index_listener')
            ->class(AdminPageIndexListener::class)
            ->args([
                new Reference('sulu_message_bus'),
            ])
            ->tag('kernel.event_listener', ['event' => PageCreatedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageModifiedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageRemovedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageRestoredEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageTranslationRestoredEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageTranslationAddedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageTranslationRemovedEvent::class, 'method' => 'onPageChanged']);

        $services->set('sulu_page.admin_page_reindex_provider')
            ->class(AdminPageReindexProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                tagged_iterator('sulu_page.admin_page_reindex_provider_enhancer'),
            ])
            ->tag('cmsig_seal.reindex_provider');

        $services->set('sulu_page.website_page_index_listener')
            ->class(WebsitePageIndexListener::class)
            ->args([
                new Reference('sulu_message_bus'),
            ])
            ->tag('kernel.event_listener', ['event' => PageWorkflowTransitionAppliedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageRemovedEvent::class, 'method' => 'onPageChanged'])
            ->tag('kernel.event_listener', ['event' => PageTranslationRemovedEvent::class, 'method' => 'onPageChanged']);

        $builder->registerForAutoconfiguration(AdminPageReindexProviderEnhancerInterface::class)
            ->addTag('sulu_page.admin_page_reindex_provider_enhancer');

        $builder->registerForAutoconfiguration(WebsitePageReindexProviderEnhancerInterface::class)
            ->addTag('sulu_page.website_page_reindex_provider_enhancer');

        $services->set('sulu_page.website_page_reindex_content_enhancer')
            ->class(WebsitePageReindexContentEnhancer::class)
            ->args([
                new Reference('sulu_admin.form_metadata_provider'),
            ])
            ->tag('sulu_page.website_page_reindex_provider_enhancer');

        $services->set('sulu_page.website_page_reindex_excerpt_enhancer')
            ->class(WebsitePageReindexExcerptEnhancer::class)
            ->tag('sulu_page.website_page_reindex_provider_enhancer');

        $services->set('sulu_page.website_page_reindex_taxonomy_enhancer')
            ->class(WebsitePageReindexTaxonomyEnhancer::class)
            ->tag('sulu_page.website_page_reindex_provider_enhancer');

        $services->set('sulu_page.website_page_reindex_provider')
            ->class(WebsitePageReindexProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                tagged_iterator('sulu_page.website_page_reindex_provider_enhancer'),
            ])
            ->tag('cmsig_seal.reindex_provider');

        // Security
        $services->set('sulu_page.page_security_listener')
            ->class(PageSecurityListener::class)
            ->args([
                new Reference('sulu_security.security_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('kernel.event_subscriber');

        $services->set('sulu_page.page_segment_listener')
            ->class(PageSegmentListener::class)
            ->args([
                new Reference('sulu_core.webspace.request_analyzer'),
            ])
            ->tag('kernel.event_subscriber');

        $services->set('sulu_page.page_descendant_security_listener')
            ->class(PageDescendantSecurityListener::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu.repository.access_control'),
                new Reference('sulu_security.system_store'),
                new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                param('sulu_security.permissions'),
            ])
            ->tag('kernel.event_subscriber');

        // Cache Invalidation
        $services->set('sulu_page.page_cache_invalidation_subscriber')
            ->class(PageCacheInvalidationSubscriber::class)
            ->args([
                new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('sulu_route.route_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_route.route_generator'),
            ])
            ->tag('kernel.event_subscriber');
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('sulu_admin')) {
            $builder->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            \dirname(__DIR__, 4) . '/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            \dirname(__DIR__, 4) . '/config/forms',
                        ],
                    ],
                    'templates' => [
                        PageInterface::TEMPLATE_TYPE => [
                            'default_type' => null,
                            'directories' => [
                                'app' => '%kernel.project_dir%/config/templates/pages',
                            ],
                        ],
                    ],
                    'resources' => [
                        'pages' => [
                            'routes' => [
                                'list' => 'sulu_page.get_pages',
                                'detail' => 'sulu_page.get_page',
                            ],
                            'security_class' => Page::class,
                            'security_context' => 'sulu.webspaces.#webspace#',
                        ],
                        'pages_versions' => [
                            'routes' => [
                                'list' => 'sulu_page.get_page_versions',
                                'detail' => 'sulu_page.get_page',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'page_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'pages',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'column_list',
                                        'list_key' => 'pages',
                                        'display_properties' => ['title', 'routePath'],
                                        'icon' => 'su-newspaper',
                                        'label' => 'sulu_page.selection_label',
                                        'overlay_title' => 'sulu_page.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_page_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'pages',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'column_list',
                                        'list_key' => 'pages',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_page.no_page_selected',
                                        'icon' => 'su-newspaper',
                                        'overlay_title' => 'sulu_page.single_selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluPage' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Page\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Page',
                                'alias' => 'SuluPage',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                        'hydrators' => [
                            'sulu_page_tree' => SafeTreeObjectHydrator::class,
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('doctrine_migrations')) {
            $builder->prependExtensionConfig(
                'doctrine_migrations',
                [
                    'migrations_paths' => [
                        'Sulu\\Page\\Migrations' => \dirname(__DIR__, 4) . '/migrations',
                    ],
                ],
            );
        }

        if ($builder->hasExtension('jms_serializer')) {
            $builder->prependExtensionConfig(
                'jms_serializer',
                [
                    'metadata' => [
                        'directories' => [
                            [
                                'name' => 'sulu_page.webspace',
                                'path' => __DIR__ . '/../../../../config/serializer',
                                'namespace_prefix' => 'Sulu\Component\Webspace',
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_search')) {
            $builder->prependExtensionConfig(
                'sulu_search',
                [
                    'admin' => [
                        'resources' => [
                            PageInterface::RESOURCE_KEY => [
                                'name' => 'sulu_page.pages',
                                'icon' => 'su-document',
                                'route' => [
                                    'name' => PageAdmin::EDIT_FORM_VIEW,
                                    'resultToRoute' => [
                                        'resourceId' => 'id',
                                        'locale' => 'locale',
                                        'metadata.webspaceKey' => 'webspace',
                                    ],
                                ],
                                'securityContext' => PageAdmin::SECURITY_CONTEXT_GROUP, // Todo: Add correct permissions for webspaces.
                            ],
                        ],
                    ],
                ],
            );
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence([
            PageInterface::class => 'sulu.model.page.class',
            PageDimensionContentInterface::class => 'sulu.model.page_content.class',
        ], $container);
        $container->addCompilerPass(new OverrideTreeListenerPass());
    }
}
