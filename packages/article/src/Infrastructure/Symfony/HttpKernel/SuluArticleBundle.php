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
use Sulu\Article\Application\MessageHandler\RestoreArticleVersionMessageHandler;
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
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\ArticleSelectionPropertyResolver;
use Sulu\Article\Infrastructure\Sulu\Content\PropertyResolver\SingleArticleSelectionPropertyResolver;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Article\UserInterface\Controller\Admin\ArticleController;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore;
use Sulu\Content\Infrastructure\Sulu\Preview\ContentObjectProvider;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
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
        $this->configurePersistence($config['objects'], $builder); // @phpstan-ignore-line

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
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.modify_article_handler')
            ->class(ModifyArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                tagged_iterator('sulu_article.article_mapper'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.remove_article_handler')
            ->class(RemoveArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.apply_workflow_transition_article_handler')
            ->class(ApplyWorkflowTransitionArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_workflow'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.copy_locale_article_handler')
            ->class(CopyLocaleArticleMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_copier'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_article.restore_article_version_handler')
            ->class(RestoreArticleVersionMessageHandler::class)
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_content.content_copier'),
            ])
            ->tag('messenger.message_handler');

        // Mapper service
        $services->set('sulu_article.article_content_mapper')
            ->class(ArticleContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_article.article_mapper');

        // Sulu Integration service
        $services->set('sulu_article.article_admin')
            ->class(ArticleAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu.core.localization_manager'),
                new Reference('sulu_activity.activity_list_view_builder_factory'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Repositories services
        $services->set('sulu_article.article_repository')
            ->class(ArticleRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(ArticleRepositoryInterface::class, 'sulu_article.article_repository');

        // Controllers services
        $services->set('sulu_article.admin_article_controller')
            ->class(ArticleController::class)
            ->public()
            ->args([
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_message_bus'),
                new Reference('serializer'),
                // additional services to be removed when no longer needed
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        // PropertyResolver services
        $services->set('sulu_article.single_article_selection_property_resolver')
            ->class(SingleArticleSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        $services->set('sulu_article.article_selection_property_resolver')
            ->class(ArticleSelectionPropertyResolver::class)
            ->tag('sulu_content.property_resolver');

        // ResourceLoader services
        $services->set('sulu_article.article_resource_loader')
            ->class(ArticleResourceLoader::class)
            ->args([
                new Reference('sulu_article.article_repository'),
            ])
            ->tag('sulu_content.resource_loader', ['type' => ArticleResourceLoader::RESOURCE_LOADER_KEY]);

        // Preview service
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

        // Content services
        $services->set('sulu_article.article_teaser_provider')
            ->class(ArticleTeaserProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'), // TODO teaser provider should not build on manager
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.content_metadata_inspector'),
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference('translator'),
            ])
            ->tag('sulu.teaser.provider', ['alias' => ArticleInterface::RESOURCE_KEY]);

        $services->set('sulu_article.article_link_provider')
            ->class(ArticleLinkProvider::class)
            ->args([
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_article.article_repository'),
                new Reference('sulu_article.article_reference_store'),
                new Reference('translator'),
            ])
            ->tag('sulu.link.provider', ['alias' => 'article']);

        $services->set('sulu_article.article_reference_store')
            ->class(ReferenceStore::class)
            ->tag('sulu_website.reference_store', ['alias' => ArticleInterface::RESOURCE_KEY]);

        // Smart Content services
        $services->set('sulu_article.article_smart_content_provider')
            ->class(ArticleSmartContentProvider::class)
            ->args([
                new Reference('sulu_content.dimension_content_query_enhancer'),
                new Reference('sulu_admin.smart_content_query_enhancer'),
                new Reference('doctrine.orm.entity_manager'),
            ])
        ->tag('sulu_content.smart_content_provider', ['type' => ArticleInterface::RESOURCE_KEY]);
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
                            // \dirname(__DIR__, 4) . '/config/forms',
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

        if ($builder->hasExtension('sulu_core')) {
            $builder->prependExtensionConfig(
                'sulu_core',
                [
                    'content' => [
                        'structure' => [
                            'paths' => [
                                ArticleInterface::TEMPLATE_TYPE => [
                                    'path' => '%kernel.project_dir%/config/templates/articles',
                                    'type' => 'article',
                                ],
                            ],
                            'default_type' => [
                                ArticleInterface::TEMPLATE_TYPE => 'default',
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
                        ArticleInterface::class => [
                            'generator' => 'schema',
                            'options' => [
                                'route_schema' => '/{object["title"]}',
                            ],
                            'resource_key' => ArticleInterface::RESOURCE_KEY,
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
