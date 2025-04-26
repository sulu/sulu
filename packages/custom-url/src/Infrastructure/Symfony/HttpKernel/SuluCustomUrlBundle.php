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
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\CustomUrl\Application\Mapper\CustomUrlMapperInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlInterface;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;
use Sulu\CustomUrl\Domain\Model\CustomUrlRouteInterface;
use Sulu\CustomUrl\Infrastructure\Repository\CustomUrlRepositoryInterface;
use Sulu\CustomUrl\Infrastructure\Sulu\Admin\CustomUrlAdmin;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashItemHandler;
use Sulu\CustomUrl\Infrastructure\Sulu\Trash\CustomUrlTrashSubscriber;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluCustomUrlBundle extends AbstractBundle
{
    use PersistenceExtensionTrait;
    use PersistenceBundleTrait;

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
        $services->set('sulu_custom_urls.admin', CustomUrlAdmin::class)
            ->public()
            ->args([
                service(WebspaceManagerInterface::class),
                service(ViewBuilderFactoryInterface::class),
                service('sulu_security.security_checker'),
            ])
            ->tag('sulu.admin')
            ->tag('sulu.context', ['context' => 'admin'])
        ;
        $services->alias(CustomUrlAdmin::class, 'sulu_custom_urls.admin');

        $loader->load('document.php');
        $loader->load('symfony.php');
        $loader->load('message_handler.php');

        if ($builder->hasExtension('sulu_trash')) {
            $services->set('sulu_custom_urls.custom_url_trash_subscriber', CustomUrlTrashSubscriber::class)
                ->args([
                    service('sulu_trash.trash_manager'),
                    service(EntityManagerInterface::class),
                ])
                ->tag('sulu_document_manager.event_subscriber')
            ;
            $services->alias(CustomUrlTrashSubscriber::class, 'sulu_custom_urls.custom_url_trash_subscriber');

            $services->set('sulu_custom_urls.custom_url_trash_item_handler', CustomUrlTrashItemHandler::class)
                ->args([
                    service(CustomUrlRepositoryInterface::class),
                    service(CustomUrlMapperInterface::class),
                    service(TrashItemRepositoryInterface::class),
                    service(DocumentDomainEventCollectorInterface::class),
                    service(EntityManagerInterface::class),
                ])
                ->tag('sulu_trash.store_trash_item_handler')
                ->tag('sulu_trash.restore_trash_item_handler')
                ->tag('sulu_trash.restore_configuration_provider')
            ;
            $services->alias(CustomUrlTrashItemHandler::class, 'sulu_custom_urls.custom_url_trash_item_handler');
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
                ]
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
        parent::build($container);

        $this->buildPersistence([
            CustomUrlInterface::class => 'sulu.model.custom_url.class',
            CustomUrlRouteInterface::class => 'sulu.model.custom_url_route.class',
        ], $container);
    }
}
