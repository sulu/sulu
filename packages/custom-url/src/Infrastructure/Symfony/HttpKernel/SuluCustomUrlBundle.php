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

namespace Sulu\CustomUrl\Infrastructure\Symfony\HttpKernel;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapper;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Application\MessageHandler\CreateCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\MessageHandler\ModifyCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlMessageHandler;
use Sulu\CustomUrl\Application\MessageHandler\RemoveCustomUrlRoutesMessageHandler;
use Sulu\CustomUrl\Application\Routing\CustomUrlRouteCollectionLoader;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Domain\Repository\CustomUrlRouteRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRepository;
use Sulu\CustomUrl\Infrastructure\Doctrine\Repository\CustomUrlRouteRepository;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Sulu\CustomUrl\Infrastructure\Symfony\Serializer\CustomUrlNormalizer;
use Sulu\CustomUrl\Infrastructure\Symfony\Serializer\CustomUrlRouteNormalizer;
use Sulu\CustomUrl\UserInterface\Controller\Admin\CustomUrlController;
use Sulu\CustomUrl\UserInterface\Controller\Admin\CustomUrlRouteController;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @codeCoverageIgnore
 */
final class SuluCustomUrlBundle extends AbstractBundle
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
                        ->arrayNode('custom_url')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(CustomUrl::class)->end()
                            ->end()
                        ->end()
                        ->arrayNode('custom_url_route')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(CustomUrlRoute::class)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array{
     *       'objects': array{
     *           'custom_url': array{'model': class-string},
     *           'custom_url_route': array{'model': class-string}
     *       }
     *   } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->configurePersistence($config['objects'], $builder);

        $loader = new PhpFileLoader($builder, new FileLocator(\dirname(__DIR__, 4) . '/config'));

        $services = $container->services();

        // Mapper
        $services->set(CustomUrlMapperInterface::class, CustomUrlMapper::class)
            ->tag(CustomUrlMapperInterface::SERVICE_TAG);

        // Message Handler
        $services->set(CreateCustomUrlMessageHandler::class)
            ->args([
                tagged_iterator(CustomUrlMapperInterface::SERVICE_TAG),
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference(DomainEventCollectorInterface::class),
            ])
            ->tag('messenger.message_handler');

        $services->set(ModifyCustomUrlMessageHandler::class)
            ->args([
                tagged_iterator(CustomUrlMapperInterface::SERVICE_TAG),
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference(DomainEventCollectorInterface::class),
            ])
            ->tag('messenger.message_handler');

        $services->set(RemoveCustomUrlMessageHandler::class)
            ->args([
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference('sulu_trash.trash_manager'),
                new Reference(DomainEventCollectorInterface::class),
            ])
            ->tag('messenger.message_handler');

        $services->set(RemoveCustomUrlRoutesMessageHandler::class)
            ->args([
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference(CustomUrlRouteRepositoryInterface::class),
                new Reference(EntityManagerInterface::class),
            ])
            ->tag('messenger.message_handler');

        // Admin configuration
        $services->set('sulu_custom_urls.admin', CustomUrlAdmin::class)
            ->public()
            ->args([
                service(WebspaceManagerInterface::class),
                service(ViewBuilderFactoryInterface::class),
                service(SecurityCheckerInterface::class),
                '%kernel.environment%',
            ])
            ->tag('sulu.admin')
            ->tag('sulu.context', ['context' => 'admin']);
        $services->alias(CustomUrlAdmin::class, 'sulu_custom_urls.admin');

        $services->set('sulu_custom_urls.custom_url_controller', CustomUrlController::class)
            ->public()
            ->args([
                new Reference(MessageBusInterface::class),
                new Reference(RequestStack::class),
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference(NormalizerInterface::class),
                new Reference(FieldDescriptorFactoryInterface::class),
                new Reference(DoctrineListBuilderFactoryInterface::class),
                new Reference('sulu_core.doctrine_rest_helper'),
            ]);

        $services->set('sulu_custom_urls.custom_url_route_controller', CustomUrlRouteController::class)
            ->public()
            ->args([
                new Reference(CustomUrlRepositoryInterface::class),
                new Reference(MessageBusInterface::class),
                new Reference(NormalizerInterface::class),
                new Reference(FieldDescriptorFactoryInterface::class),
                new Reference(DoctrineListBuilderFactoryInterface::class),
                new Reference('sulu_core.doctrine_rest_helper'),
                new Reference('request_stack'),
            ]);

        // Repositories
        $services->set(CustomUrlRepositoryInterface::class, CustomUrlRepository::class)
            ->args([
                new Reference(EntityManagerInterface::class),
            ]);

        $services->alias('sulu_custom_urls.repository', CustomUrlRepositoryInterface::class);

        $services->set(CustomUrlRouteRepositoryInterface::class, CustomUrlRouteRepository::class)
            ->public()
            ->args([
                new Reference(EntityManagerInterface::class),
            ]);

        $services->alias('sulu_custom_urls.route_repository', CustomUrlRouteRepositoryInterface::class);

        // Extending Symfony
        $services->set(CustomUrlNormalizer::class)
            ->args([
                new Reference('serializer.normalizer.object'),
            ])
            ->tag('serializer.normalizer');

        $services->set(CustomUrlRouteNormalizer::class)
            ->args([
                new Reference('serializer.normalizer.object'),
            ])
            ->tag('serializer.normalizer');

        // Routing
        $services->set('sulu_custom_url.routing.route_collection_loader', CustomUrlRouteCollectionLoader::class)
            ->args([
                new Reference(CustomUrlRouteRepositoryInterface::class),
                new Reference(PageRepositoryInterface::class),
                new Reference(RouteRepositoryInterface::class),
                new Reference('sulu_content.content_aggregator'),
                new Reference('sulu_admin.metadata_provider_registry'),
                new Reference(WebspaceManagerInterface::class),
                '%kernel.environment%',
            ])
            ->tag('sulu_route.route_collection_for_request_loader', ['priority' => 35]);

        /** @var array<string, class-string> $bundles */
        $bundles = $builder->getParameter('kernel.bundles');
        if (\array_key_exists('SuluTrashBundle', $bundles)) {
            $loader->load('sulu_trash.php');
        }
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
                    'resources' => [
                        CustomUrl::RESOURCE_KEY => [
                            'routes' => [
                                'list' => 'sulu_custom_url.cget_webspace_custom-urls',
                                'detail' => 'sulu_custom_url.get_webspace_custom-urls',
                            ],
                        ],
                        'custom_url_routes' => [
                            'routes' => [
                                'list' => 'sulu_custom_url.get_webspace_custom-urls_routes',
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
                            'SuluCustomUrlBundle' => [
                                'type' => 'xml',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/CustomUrl',
                                'prefix' => 'Sulu\CustomUrl\Domain\Model',
                                'alias' => 'SuluCustomUrlBundle',
                                'is_bundle' => false,
                                'mapping' => true,
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
        $container->registerForAutoconfiguration(CustomUrlMapperInterface::class)
            ->addTag(CustomUrlMapperInterface::SERVICE_TAG);

        $this->buildPersistence([
            CustomUrlInterface::class => 'sulu.model.custom_url.class',
            CustomUrlRouteInterface::class => 'sulu.model.custom_url_route.class',
        ], $container);
    }
}
