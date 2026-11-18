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

namespace Sulu\Webspace\Infrastructure\Symfony\HttpKernel;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Webspace\Application\Mapper\WebspaceSettingContentMapper;
use Sulu\Webspace\Application\Mapper\WebspaceSettingMapperInterface;
use Sulu\Webspace\Application\MessageHandler\ApplyWorkflowTransitionWebspaceSettingMessageHandler;
use Sulu\Webspace\Application\MessageHandler\CopyLocaleWebspaceSettingMessageHandler;
use Sulu\Webspace\Application\MessageHandler\ModifyWebspaceSettingMessageHandler;
use Sulu\Webspace\Application\MessageHandler\RestoreWebspaceSettingVersionMessageHandler;
use Sulu\Webspace\Domain\Event\WebspaceSettingCreatedEvent;
use Sulu\Webspace\Domain\Event\WebspaceSettingModifiedEvent;
use Sulu\Webspace\Domain\Event\WebspaceSettingTranslationCopiedEvent;
use Sulu\Webspace\Domain\Model\WebspaceSetting;
use Sulu\Webspace\Domain\Model\WebspaceSettingDimensionContent;
use Sulu\Webspace\Domain\Model\WebspaceSettingDimensionContentInterface;
use Sulu\Webspace\Domain\Model\WebspaceSettingInterface;
use Sulu\Webspace\Domain\Repository\WebspaceSettingRepositoryInterface;
use Sulu\Webspace\Infrastructure\Doctrine\Repository\WebspaceSettingRepository;
use Sulu\Webspace\Infrastructure\Sulu\Admin\MetadataLoader\WebspaceSettingsFormMetadataLoader;
use Sulu\Webspace\Infrastructure\Sulu\Admin\WebspaceSettingAdmin;
use Sulu\Webspace\Infrastructure\Sulu\HttpCache\EventSubscriber\WebspaceSettingCacheInvalidationSubscriber;
use Sulu\Webspace\Infrastructure\Sulu\Reference\WebspaceSettingReferenceRefresher;
use Sulu\Webspace\Infrastructure\Sulu\Search\AdminWebspaceSettingIndexListener;
use Sulu\Webspace\Infrastructure\Sulu\Search\AdminWebspaceSettingReindexProvider;
use Sulu\Webspace\Infrastructure\Symfony\Twig\Extension\WebspaceSettingsTwigExtension;
use Sulu\Webspace\UserInterface\Controller\Admin\WebspaceSettingController;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluWebspaceBundle extends AbstractBundle
{
    use PersistenceBundleTrait;
    use PersistenceExtensionTrait;

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
                ->arrayNode('objects')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('webspace_setting')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(WebspaceSetting::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('webspace_setting_content')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(WebspaceSettingDimensionContent::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{
     *     objects: array{
     *         webspace_setting: array{model: class-string},
     *         webspace_setting_content: array{model: class-string},
     *     },
     * } $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder);

        $builder->registerForAutoconfiguration(WebspaceSettingMapperInterface::class)
            ->addTag('sulu_webspace.webspace_setting_mapper');

        $services = $container->services();

        $services->set('sulu_webspace.webspace_setting_repository')
            ->class(WebspaceSettingRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_content.dimension_content_query_enhancer'),
            ]);

        $services->alias(WebspaceSettingRepositoryInterface::class, 'sulu_webspace.webspace_setting_repository');

        $services->set('sulu_webspace.webspace_setting_content_mapper')
            ->class(WebspaceSettingContentMapper::class)
            ->args([
                new Reference('sulu_content.content_persister'),
            ])
            ->tag('sulu_webspace.webspace_setting_mapper');

        $services->set('sulu_webspace.modify_webspace_setting_handler')
            ->class(ModifyWebspaceSettingMessageHandler::class)
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_core.webspace.webspace_manager'),
                tagged_iterator('sulu_webspace.webspace_setting_mapper'),
                new Reference('sulu_activity.domain_event_collector'),
                new Reference('sulu_content.content_hash_checker'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_webspace.apply_workflow_transition_webspace_setting_handler')
            ->class(ApplyWorkflowTransitionWebspaceSettingMessageHandler::class)
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_content.content_workflow'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_webspace.copy_locale_webspace_setting_handler')
            ->class(CopyLocaleWebspaceSettingMessageHandler::class)
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_webspace.restore_webspace_setting_version_handler')
            ->class(RestoreWebspaceSettingVersionMessageHandler::class)
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_content.content_copier'),
                new Reference('sulu_activity.domain_event_collector'),
            ])
            ->tag('messenger.message_handler');

        $services->set('sulu_webspace.webspace_setting_admin')
            ->class(WebspaceSettingAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
                new Reference('sulu_content.content_view_builder_factory'),
                new Reference('sulu_security.security_checker'),
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_activity.activity_list_view_builder_factory'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        $services->set('sulu_webspace.webspace_settings_form_metadata_loader')
            ->class(WebspaceSettingsFormMetadataLoader::class)
            ->args([
                new Reference('sulu_core.webspace.webspace_manager'),
                new Reference('sulu_admin.xml_form_metadata_loader'),
            ])
            ->tag('sulu_admin.form_metadata_loader', ['priority' => 256]);

        $services->set('sulu_webspace.admin_webspace_setting_controller')
            ->class(WebspaceSettingController::class)
            ->public()
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_message_bus'),
                new Reference('serializer'),
                new Reference('sulu_content.content_manager'),
                new Reference('sulu_core.list_builder.field_descriptor_factory'),
                new Reference('sulu_core.doctrine_list_builder_factory'),
                new Reference('sulu_core.doctrine_rest_helper'),
                new Reference('sulu_security.security_checker'),
                new Reference('request_stack'),
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        $services->set('sulu_webspace.webspace_settings_twig_extension')
            ->class(WebspaceSettingsTwigExtension::class)
            ->args([
                new Reference('sulu_webspace.webspace_setting_repository'),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_content.content_resolver'),
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('sulu_http_cache.reference_store'),
                new Reference('sulu_core.webspace.webspace_manager'),
            ])
            ->tag('twig.extension')
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set('sulu_webspace.webspace_setting_cache_invalidation_subscriber')
            ->class(WebspaceSettingCacheInvalidationSubscriber::class)
            ->args([
                new Reference('sulu_http_cache.cache_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->tag('kernel.event_subscriber');

        $services->set('sulu_webspace.webspace_setting_reference_refresher')
            ->class(WebspaceSettingReferenceRefresher::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
                new Reference('sulu_reference.reference_repository'),
                new Reference('sulu_content.content_view_resolver'),
                new Reference('sulu_content.content_merger'),
            ])
            ->tag('sulu_reference.refresher');

        $services->set('sulu_webspace.admin_webspace_setting_index_listener')
            ->class(AdminWebspaceSettingIndexListener::class)
            ->args([
                new Reference('sulu_message_bus'),
            ])
            ->tag('kernel.event_listener', ['event' => WebspaceSettingCreatedEvent::class, 'method' => 'onWebspaceSettingChanged'])
            ->tag('kernel.event_listener', ['event' => WebspaceSettingModifiedEvent::class, 'method' => 'onWebspaceSettingChanged'])
            ->tag('kernel.event_listener', ['event' => WebspaceSettingTranslationCopiedEvent::class, 'method' => 'onWebspaceSettingChanged']);

        $services->set('sulu_webspace.admin_webspace_setting_reindex_provider')
            ->class(AdminWebspaceSettingReindexProvider::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
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
                    'resources' => [
                        WebspaceSettingInterface::RESOURCE_KEY => [
                            'routes' => [
                                'detail' => 'sulu_webspace.get_webspace_setting',
                            ],
                            'views' => [
                                'detail' => WebspaceSettingAdmin::TABS_VIEW,
                            ],
                            'security_class' => WebspaceSetting::class,
                            'security_context' => 'sulu.webspaces.#webspace#.webspace-settings',
                        ],
                        WebspaceSettingInterface::RESOURCE_KEY . '_versions' => [
                            'routes' => [
                                'list' => 'sulu_webspace.get_webspace_setting_versions',
                                'detail' => 'sulu_webspace.get_webspace_setting',
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
                            'SuluWebspace' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Webspace\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Webspace',
                                'alias' => 'SuluWebspace',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
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
                        'Sulu\\Webspace\\Migrations' => \dirname(__DIR__, 4) . '/migrations',
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
                            WebspaceSettingInterface::RESOURCE_KEY => [
                                'name' => 'sulu_webspace.webspace_settings_tab',
                                'icon' => 'su-cog',
                                'route' => [
                                    'name' => WebspaceSettingAdmin::TABS_VIEW,
                                    'resultToRoute' => [
                                        'resourceId' => 'id',
                                        'locale' => 'locale',
                                        'metadata.webspaceKey' => 'webspace',
                                    ],
                                ],
                                'securityContext' => PageAdmin::SECURITY_CONTEXT_GROUP,
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
            WebspaceSettingInterface::class => 'sulu.model.webspace_setting.class',
            WebspaceSettingDimensionContentInterface::class => 'sulu.model.webspace_setting_content.class',
        ], $container);
    }
}
