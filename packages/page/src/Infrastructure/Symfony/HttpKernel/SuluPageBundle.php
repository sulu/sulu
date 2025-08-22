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

use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
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
use Sulu\Page\Application\MessageHandler\RestorePageVersionMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Infrastructure\Doctrine\Repository\NavigationRepository;
use Sulu\Page\Infrastructure\Doctrine\Repository\PageRepository;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Admin\PropertyMetadataMapper\PageTreeRoutePropertyMetadataMapper;
use Sulu\Page\Infrastructure\Sulu\Build\HomepageBuilder;
use Sulu\Page\Infrastructure\Sulu\Content\DataMapper\NavigationContextDataMapper;
use Sulu\Page\Infrastructure\Sulu\Content\Merger\NavigationContextMerger;
use Sulu\Page\Infrastructure\Sulu\Content\Normalizer\PageNormalizer;
use Sulu\Page\Infrastructure\Sulu\Content\PageLinkProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PageSmartContentProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PageTeaserProvider;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\BlockVisitor\SegmentBlockVisitor;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\PageSelectionPropertyResolver;
use Sulu\Page\Infrastructure\Sulu\Content\PropertyResolver\SinglePageSelectionPropertyResolver;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Sulu\Page\Infrastructure\Sulu\Content\Visitor\SegmentSmartContentFiltersVisitor;
use Sulu\Page\Infrastructure\Sulu\Reference\PageReferenceRefresher;
use Sulu\Page\Infrastructure\Sulu\Route\WebspaceSiteRouteGenerator;
use Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension;
use Sulu\Page\UserInterface\Command\InitializeHomepageCommand;
use Sulu\Page\UserInterface\Controller\Admin\PageController;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluPageBundle extends AbstractBundle
{
    use PersistenceExtensionTrait;
    use PersistenceBundleTrait;

    public function __construct()
    {
        $this->name = 'SuluNextPageBundle';
        $this->extensionAlias = 'sulu_next_page';
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
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.modify_page_handler')
            ->class(ModifyPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                tagged_iterator('sulu_page.page_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.remove_page_handler')
            ->class(RemovePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.apply_workflow_transition_page_handler')
            ->class(ApplyWorkflowTransitionPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_workflow'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.copy_locale_page_handler')
            ->class(CopyLocalePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.order_page_handler')
            ->class(OrderPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.move_page_handler')
            ->class(MovePageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.copy_page_handler')
            ->class(CopyPageMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu.core.localization_manager'),
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_page.restore_page_version_handler')
            ->class(RestorePageVersionMessageHandler::class)
            ->args([
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_content.content_copier'),
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

        // Normalizer service
        $services->set('sulu_page.page_normalizer')
            ->class(PageNormalizer::class)
            ->tag('sulu_content.normalizer');

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
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Route Integration
        $services->set('sulu_page.webspace_site_route_generator')
            ->class(WebspaceSiteRouteGenerator::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('request_stack'),
            ])
            ->tag('sulu_route.site_route_generator', ['site' => '.default'])
        ;

        // Repositories services
        $services->set('sulu_page.page_repository')
            ->class(PageRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(PageRepositoryInterface::class, 'sulu_page.page_repository');

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
                new Reference('doctrine.orm.entity_manager'),
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
                null, //TODO add security context for preview
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu_preview.object_provider', ['provider-key' => 'pages']);

        // Content services
        $services->set('sulu_page.page_teaser_provider')
            ->class(PageTeaserProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'), // TODO teaser provider should not build on manager
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_metadata_inspector'),
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('translator'),
            ])
            ->tag('sulu.teaser.provider', ['alias' => PageInterface::RESOURCE_KEY]);

        $services->set('sulu_page.page_link_provider')
            ->class(PageLinkProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_page.page_repository'),
                new Reference('sulu_page.page_reference_store'),
                new Reference('translator'),
            ])
            ->tag('sulu.link.provider', ['alias' => 'page']);

        $services->set('sulu_page.page_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => PageInterface::RESOURCE_KEY]);

        // Smart Content services
        $services->set('sulu_page.page_smart_content_provider')
            ->class(PageSmartContentProvider::class)
            ->args([
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_admin.form_metadata_provider'),
                new Reference('sulu_admin.smart_content_query_enhancer'),
                new Reference('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                new Reference('doctrine.orm.entity_manager'),
            ])
            ->tag('sulu_content.smart_content_provider', ['type' => PageInterface::RESOURCE_KEY]);

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
            ]);

        $services->alias(NavigationRepository::class, 'sulu_page.navigation_repository');

        $services->set('sulu_page.navigation_twig_extension')
            ->class(NavigationTwigExtension::class)
            ->args([
                new Reference('sulu_page.navigation_repository'),
                new Reference('sulu_core.webspace.request_analyzer'),
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
                                        'adapter' => 'table',
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
                                        'adapter' => 'table',
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
                            'sulu_page_tree' => TreeObjectHydrator::class,
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_core')) {
            $builder->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                PageInterface::TEMPLATE_TYPE => [
                                    'path' => '%kernel.project_dir%/config/templates/pages',
                                    'type' => 'page',
                                ],
                            ],
                            'default_type' => [
                                PageInterface::TEMPLATE_TYPE => 'default',
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('sulu_route')) {
            $builder->prependExtensionConfig(
                'sulu_route',
                [
                    'mappings' => [
                        PageInterface::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => PageInterface::RESOURCE_KEY,
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
    }
}
