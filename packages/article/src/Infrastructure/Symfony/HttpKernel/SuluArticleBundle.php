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

namespace Sulu\Article\Infrastructure\Symfony\HttpKernel;

use Sulu\Article\Application\Mapper\ArticleContentMapper;
use Sulu\Article\Application\Mapper\ArticleMapperInterface;
use Sulu\Article\Application\MessageHandler\ApplyWorkflowTransitionArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\CopyLocaleArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\CreateArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\ModifyArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\RemoveArticleMessageHandler;
use Sulu\Article\Application\MessageHandler\RemoveArticleTranslationMessageHandler;
use Sulu\Article\Application\MessageHandler\RestoreArticleVersionMessageHandler;
use Sulu\Article\Application\Webspace\WebspaceSettingsConfigurationResolver;
use Sulu\Article\Domain\Event\ArticleCreatedEvent;
use Sulu\Article\Domain\Event\ArticleModifiedEvent;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleRestoredEvent;
use Sulu\Article\Domain\Event\ArticleTranslationAddedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationCopiedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRestoredEvent;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\Article;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Infrastructure\Doctrine\Repository\ArticleRepository;
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleLinkProvider;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleSmartContentProvider;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleTeaserProvider;
use Sulu\Article\Infrastructure\Sulu\Content\DataMapper\AdditionalWebspacesDataMapper;
use Sulu\Article\Infrastructure\Sulu\Content\Merger\AdditionalWebspacesMerger;
use Sulu\Article\Infrastructure\Sulu\Content\PageTreeArticleSmartContentProvider;
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\ArticleSelectionPropertyResolver;
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\SingleArticleSelectionPropertyResolver;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Article\Infrastructure\Sulu\HttpCache\EventSubscriber\ArticleCacheInvalidationSubscriber;
use Sulu\Article\Infrastructure\Sulu\Reference\ArticleReferenceRefresher;
use Sulu\Article\Infrastructure\Sulu\Route\ArticleRouteDefaultsProvider;
use Sulu\Article\Infrastructure\Sulu\Search\AdminArticleIndexListener;
use Sulu\Article\Infrastructure\Sulu\Search\AdminArticleReindexProvider;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\AdminArticleReindexProviderEnhancerInterface;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexContentEnhancer;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexExcerptEnhancer;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexProviderEnhancerInterface;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexTaxonomyEnhancer;
use Sulu\Article\Infrastructure\Sulu\Search\WebsiteArticleIndexListener;
use Sulu\Article\Infrastructure\Sulu\Search\WebsiteArticleReindexProvider;
use Sulu\Article\Infrastructure\Sulu\Sitemap\ArticlesSitemapProvider;
use Sulu\Article\Infrastructure\Sulu\Trash\ArticleTrashItemHandler;
use Sulu\Article\Infrastructure\Symfony\Twig\ArticleTwigExtension;
use Sulu\Article\UserInterface\Controller\Admin\ArticleController;
use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluArticleBundle extends AbstractBundle
{
    use PersistenceBundleTrait;
    use PersistenceExtensionTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('default_main_webspace')
                    ->useAttributeAsKey('locale')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('default_additional_webspaces')
                    ->beforeNormalization()
                        ->ifTrue(function($v) {
                            if (!\is_array($v)) {
                                return false;
                            }

                            return \count(\array_filter(\array_keys($v), 'is_string')) <= 0;
                        })
                        ->then(function($v) {
                            return ['default' => $v];
                        })
                    ->end()
                    ->prototype('array')->useAttributeAsKey('locale')->prototype('scalar')->end()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('article')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Article::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('article_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(ArticleDimensionContent::class)->end()
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
        /** @var array<string, array{model: class-string, repository?: class-string}> $objects */
        $objects = $config['objects'] ?? [];
        $this->configurePersistence($objects, $builder);

        /** @var array<string, string> $defaultMainWebspace */
        $defaultMainWebspace = $config['default_main_webspace'] ?? [];
        /** @var array<string, array<string>> $defaultAdditionalWebspaces */
        $defaultAdditionalWebspaces = $config['default_additional_webspaces'] ?? [];
        $builder->setParameter('sulu_article.default_main_webspace', $defaultMainWebspace);
        $builder->setParameter('sulu_article.default_additional_webspaces', $defaultAdditionalWebspaces);

        $services = $container->services();

        // Define autoconfigure interfaces for mappers
        $builder->registerForAutoconfiguration(ArticleMapperInterface::class)
            ->addTag('sulu_article.article_mapper');

        // Message Handler services
        $services->set('sulu_article.create_article_handler')
            ->class(CreateArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                tagged_iterator('sulu_article.article_mapper'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.modify_article_handler')
            ->class(ModifyArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                tagged_iterator('sulu_article.article_mapper'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.remove_article_handler')
            ->class(RemoveArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.remove_article_translation_handler')
            ->class(RemoveArticleTranslationMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_trash.trash_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.apply_workflow_transition_article_handler')
            ->class(ApplyWorkflowTransitionArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_workflow'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.copy_locale_article_handler')
            ->class(CopyLocaleArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.restore_article_version_handler')
            ->class(RestoreArticleVersionMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.article_content_mapper')
            ->class(ArticleContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_article.article_mapper');

        $services->set('sulu_article.additional_webspaces_data_mapper')
            ->class(AdditionalWebspacesDataMapper::class)
            ->args([
                new Reference('sulu_article.webspace_settings_configuration_resolver'),
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('sulu_content.data_mapper');

        $services->set('sulu_article.additional_webspaces_merger')
            ->class(AdditionalWebspacesMerger::class)
            ->tag('sulu_content.merger', ['priority' => 12]);

        $services->set('sulu_article.webspace_settings_configuration_resolver')
            ->class(WebspaceSettingsConfigurationResolver::class)
            ->args([
                '%sulu_article.default_main_webspace%',
                '%sulu_article.default_additional_webspaces%',
                new Reference('sulu_core.webspace.webspace_manager'),
            ]);

        $services->set('sulu_article.article_admin')
            ->class(ArticleAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu.core.localization_manager'),
                new Reference('sulu_activity.activity_list_view_builder_factory'),
                new Reference('sulu_admin.metadata_group_provider'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        $services->set('sulu_article.article_repository')
            ->class(ArticleRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(ArticleRepositoryInterface::class, 'sulu_article.article_repository');
        $services->alias(ArticleRepository::class, 'sulu_article.article_repository');

        $services->set('sulu_article.admin_article_controller')
            ->class(ArticleController::class)
            ->public()
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_message_bus'),
                new Reference('serializer'),
                new Reference('sulu_admin.metadata_group_provider'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
                param('sulu_core.is_single_locale'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        $services->set('sulu_article.single_article_selection_property_resolver')
            ->class(SingleArticleSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_article.article_selection_property_resolver')
            ->class(ArticleSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_article.article_resource_loader')
            ->class(ArticleResourceLoader::class)
            ->args([
                new Reference('sulu_article.article_repository'),
            ])
            ->tag('sulu_content.resource_loader', ['type' => ArticleResourceLoader::RESOURCE_LOADER_KEY]);

        $services->set('sulu_article.article_preview_provider')
            ->class(ContentObjectProvider::class)
            ->args([
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_data_mapper'),
                '%sulu.model.article.class%',
                ArticleAdmin::SECURITY_CONTEXT,
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu_preview.object_provider', ['provider-key' => 'articles']);

        $services->set('sulu_article.article_teaser_provider')
            ->class(ArticleTeaserProvider::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_enhancer'),
                new Reference('translator'),
                new Reference('sulu_admin.teaser_tag_property_extractor'),
            ])
            ->tag('sulu.teaser.provider', ['alias' => ArticleInterface::RESOURCE_KEY]);

        $services->set('sulu_article.article_link_provider')
            ->class(ArticleLinkProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_route.route_generator'),
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('sulu_http_cache.reference_store'),
                new Reference('translator'),
                '%sulu.model.article_content.class%',
            ])
            ->tag('sulu.link.provider', ['alias' => 'article']);

        // Smart Content services
        $services->set('sulu_article.article_smart_content_provider')
            ->class(ArticleSmartContentProvider::class)
            ->args([
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_admin.smart_content_query_enhancer'),
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_admin.metadata_group_provider'),
            ])
        ->tag('sulu_content.smart_content_provider', ['type' => ArticleInterface::RESOURCE_KEY]);

        $services->set('sulu_article.page_tree_article_smart_content_provider')
            ->class(PageTreeArticleSmartContentProvider::class)
            ->args([
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_admin.smart_content_query_enhancer'),
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_admin.metadata_group_provider'),
            ])
            ->tag('sulu_content.smart_content_provider', ['type' => PageTreeArticleSmartContentProvider::PROVIDER_TYPE]);

        $services->set('sulu_article.article_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => ArticleInterface::RESOURCE_KEY]);

        // Twig Extensions
        $services->set('sulu_article.article_twig_extension')
            ->class(ArticleTwigExtension::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('sulu_http_cache.reference_store'),
                new Reference('sulu_content.content_resolver'),
            ])
            ->tag('twig.extension');

        // Reference services
        $services->set('sulu_article.article_reference_refresher')
            ->class(ArticleReferenceRefresher::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_reference.reference_repository'),
                new Reference('sulu_content.content_view_resolver'),
                new Reference('sulu_content.content_merger'),
            ])
            ->tag('sulu_reference.refresher');

        // Cache Invalidation
        $services->set('sulu_article.article_cache_invalidation_subscriber')
            ->class(ArticleCacheInvalidationSubscriber::class)
            ->args([
                new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('sulu_route.route_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_route.route_generator'),
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('kernel.event_subscriber');

        // Sitemap
        $services->set('sulu_article.articles_sitemap_provider')
            ->class(ArticlesSitemapProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_core.webspace.webspace_manager'),
                '%kernel.environment%',
                '%sulu_article.default_main_webspace%',
                '%sulu_article.default_additional_webspaces%',
            ])
            ->tag('sulu.sitemap.provider');

        // Trash services
        /** @var array<string, class-string> $bundles */
        $bundles = $builder->getParameter('kernel.bundles');
        if (isset($bundles['SuluTrashBundle'])) {
            $services->set('sulu_article.trash_item_handler')
                ->class(ArticleTrashItemHandler::class)
                ->args([
                    new Reference('sulu_trash.trash_item_repository'),
                    new Reference('sulu_article.article_repository'),
                    new Reference('sulu_content.content_normalizer'),
                    new Reference('sulu_content.content_merger'),
                    tagged_iterator('sulu_article.article_mapper'),
                    new Reference('sulu_activity.domain_event_collector'),
                ])
                ->tag('sulu_trash.store_trash_item_handler')
                ->tag('sulu_trash.restore_trash_item_handler')
                ->tag('sulu_trash.restore_configuration_provider');
        }

        $services->set('sulu_article.article_route_defaults_provider')
            ->class(ArticleRouteDefaultsProvider::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('sulu_http_cache.cache_lifetime.resolver'),
            ])
            ->tag('sulu_route.route_defaults_provider', ['resource_key' => 'articles']);

        $services->set('sulu_article.admin_article_index_listener')
            ->class(AdminArticleIndexListener::class)
            ->args([
                new Reference('sulu_message_bus'),
            ])
            ->tag('kernel.event_listener', ['event' => ArticleCreatedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleModifiedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleRemovedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleRestoredEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleTranslationRestoredEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleTranslationAddedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleTranslationRemovedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleTranslationCopiedEvent::class, 'method' => 'onArticleChanged']);

        $services->set('sulu_article.admin_article_reindex_provider')
            ->class(AdminArticleReindexProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_admin.metadata_group_provider'),
                tagged_iterator('sulu_article.admin_article_reindex_provider_enhancer'),
            ])
            ->tag('cmsig_seal.reindex_provider');

        $services->set('sulu_article.website_article_index_listener')
            ->class(WebsiteArticleIndexListener::class)
            ->args([
                new Reference('sulu_message_bus'),
            ])
            ->tag('kernel.event_listener', ['event' => ArticleWorkflowTransitionAppliedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleRemovedEvent::class, 'method' => 'onArticleChanged'])
            ->tag('kernel.event_listener', ['event' => ArticleTranslationRemovedEvent::class, 'method' => 'onArticleChanged']);

        $builder->registerForAutoconfiguration(AdminArticleReindexProviderEnhancerInterface::class)
            ->addTag('sulu_article.admin_article_reindex_provider_enhancer');

        $builder->registerForAutoconfiguration(WebsiteArticleReindexProviderEnhancerInterface::class)
            ->addTag('sulu_article.website_article_reindex_provider_enhancer');

        $services->set('sulu_article.website_article_reindex_content_enhancer')
            ->class(WebsiteArticleReindexContentEnhancer::class)
            ->args([
                new Reference('sulu_admin.form_metadata_provider'),
            ])
            ->tag('sulu_article.website_article_reindex_provider_enhancer');

        $services->set('sulu_article.website_article_reindex_excerpt_enhancer')
            ->class(WebsiteArticleReindexExcerptEnhancer::class)
            ->tag('sulu_article.website_article_reindex_provider_enhancer');

        $services->set('sulu_article.website_article_reindex_taxonomy_enhancer')
            ->class(WebsiteArticleReindexTaxonomyEnhancer::class)
            ->tag('sulu_article.website_article_reindex_provider_enhancer');

        $services->set('sulu_article.website_article_reindex_provider')
            ->class(WebsiteArticleReindexProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                tagged_iterator('sulu_article.website_article_reindex_provider_enhancer'),
            ])
            ->tag('cmsig_seal.reindex_provider');
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
                        ArticleInterface::TEMPLATE_TYPE => [
                            'default_type' => null,
                            'directories' => [
                                'app' => '%kernel.project_dir%/config/templates/articles',
                            ],
                        ],
                    ],
                    'resources' => [
                        'articles' => [
                            'routes' => [
                                'list' => 'sulu_article.get_articles',
                                'detail' => 'sulu_article.get_article',
                            ],
                        ],
                        'articles_versions' => [
                            'routes' => [
                                'list' => 'sulu_article.get_article_versions',
                                'detail' => 'sulu_article.get_article',
                            ],
                        ],
                    ],
                    'field_type_options' => [
                        'selection' => [
                            'article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title', 'routePath'],
                                        'icon' => 'su-newspaper',
                                        'label' => 'sulu_article.selection_label',
                                        'overlay_title' => 'sulu_article.selection_overlay_title',
                                    ],
                                ],
                            ],
                        ],
                        'single_selection' => [
                            'single_article_selection' => [
                                'default_type' => 'list_overlay',
                                'resource_key' => 'articles',
                                'types' => [
                                    'list_overlay' => [
                                        'adapter' => 'table',
                                        'list_key' => 'articles',
                                        'display_properties' => ['title'],
                                        'empty_text' => 'sulu_article.no_article_selected',
                                        'icon' => 'su-newspaper',
                                        'overlay_title' => 'sulu_article.single_selection_overlay_title',
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
                            'SuluArticle' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Article\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Article',
                                'alias' => 'SuluArticle',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_search')) {
            $suluSearchConfigs = $builder->getExtensionConfig('sulu_search');

            foreach ($suluSearchConfigs as $suluSearchConfig) {
                if (isset($suluSearchConfig['website']) && \is_array($suluSearchConfig['website']) && isset($suluSearchConfig['website']['indexes'])) {
                    $builder->prependExtensionConfig(
                        'sulu_search',
                        [
                            'website' => [
                                'indexes' => [
                                    ArticleInterface::RESOURCE_KEY => ArticleInterface::RESOURCE_KEY . '_published',
                                ],
                            ],
                        ],
                    );
                }
            }
        }

        if ($builder->hasExtension('sulu_search')) {
            $builder->prependExtensionConfig(
                'sulu_search',
                [
                    'admin' => [
                        'resources' => [
                            ArticleInterface::RESOURCE_KEY => [
                                'name' => 'sulu_article.articles',
                                'icon' => 'su-newspaper',
                                'route' => [
                                    'name' => ArticleAdmin::EDIT_TABS_VIEW . '_{group}',
                                    'resultToRouteName' => [
                                        'metadata.group' => 'group',
                                    ],
                                    'resultToRoute' => [
                                        'resourceId' => 'id',
                                        'locale' => 'locale',
                                    ],
                                ],
                                'securityContext' => ArticleAdmin::SECURITY_CONTEXT,
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
            ArticleInterface::class => 'sulu.model.article.class',
            ArticleDimensionContentInterface::class => 'sulu.model.article_content.class',
        ], $container);
    }
}
