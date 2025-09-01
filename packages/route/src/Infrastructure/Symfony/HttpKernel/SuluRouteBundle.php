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

namespace Sulu\Route\Infrastructure\Symfony\HttpKernel;

use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanup;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorGenerator;
use Sulu\Route\Application\Routing\Generator\RouteGenerator;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Application\Routing\Generator\SiteRouteGeneratorInterface;
use Sulu\Route\Application\Routing\Matcher\RouteCollectionForRequestLoaderInterface;
use Sulu\Route\Application\Routing\Matcher\RouteCollectionForRequestRouteLoader;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Application\Routing\Matcher\RouteHistoryDefaultsProvider;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Infrastructure\Doctrine\EventListener\RouteChangedUpdater;
use Sulu\Route\Infrastructure\Doctrine\Repository\RouteRepository;
use Sulu\Route\Infrastructure\Symfony\DependencyInjection\RouteDefaultsOptionsCompilerPass;
use Sulu\Route\Infrastructure\SymfonyCmf\Routing\CmfRouteProvider;
use Sulu\Route\Userinterface\Controller\Admin\ResourceLocatorGenerateController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_locator;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluRouteBundle extends AbstractBundle
{
    public function __construct()
    {
        $this->name = 'SuluNextRouteBundle';
        $this->extensionAlias = 'sulu_next_route'; // TODO also change route table from `ro_next_routes` to `ro_routes`
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RouteDefaultsOptionsCompilerPass());
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // TODO bridge to keep old route, custom url, redirect bundle working remove the deprecated_service_bridge.xml and make the `sulu_route.symfony_cmf_route_provider` default route provider
        $loader = new XmlFileLoader($builder, new FileLocator(\dirname(__DIR__, 4) . '/config'));
        $loader->load('deprecated_service_bridge.xml');

        $services = $container->services();

        // Doctrine Route Updater Listener
        $services->set('sulu_route.doctrine_route_changed_updater')
            ->class(RouteChangedUpdater::class)
            ->tag('doctrine.event_listener', ['event' => 'preUpdate', 'entity' => Route::class, 'method' => 'preUpdate'])
            ->tag('doctrine.event_listener', ['event' => 'prePersist', 'entity' => Route::class, 'method' => 'prePersist'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush', 'method' => 'postFlush', 'priority' => 1000])
            ->tag('doctrine.event_listener', ['event' => 'onClear', 'method' => 'onClear'])
            ->tag('kernel.reset', ['method' => 'reset']);

        // Repositories services
        $services->set('sulu_route.route_repository')
            ->class(RouteRepository::class)
            ->args([
                new Reference('doctrine.orm.entity_manager'),
            ]);

        $services->alias(RouteRepositoryInterface::class, 'sulu_route.route_repository')
            ->public();

        $services->set('sulu_route.symfony_cmf_route_provider')
            ->class(CmfRouteProvider::class)
            ->args([
                tagged_iterator('sulu_route.route_collection_for_request_loader'),
                param('sulu_route.route_default_options'),
            ]);

        $services->set('sulu_route.route_generator')
            ->class(RouteGenerator::class)
            ->args([
                tagged_locator('sulu_route.site_route_generator', 'site', 'getSite'),
                new Reference('router.request_context'),
                new Reference('request_stack'),
            ]);

        $sluggerService = $services->set('sulu_route.slugger')
            ->class(AsciiSlugger::class);

        if (
            !\method_exists(\Symfony\Component\String\AbstractUnicodeString::class, 'localeUpper') // BC Layer <= Symfony 7.0 // @phpstan-ignore-line function.alreadyNarrowedType
            || \class_exists(\Symfony\Component\Emoji\EmojiTransliterator::class) // Symfony >= 7.1 requires symfony/emoji
        ) {
            $sluggerService->call('withEmoji');
        }

        $services->set('sulu_route.path_cleanup')
            ->class(PathCleanup::class)
            ->args([
                new Reference('sulu_route.slugger'),
                // TODO replacers for `&` and what is not handled by the slugger
            ]);

        $services->alias(PathCleanupInterface::class, 'sulu_route.path_cleanup')
            ->public();

        $services->alias(RouteGeneratorInterface::class, 'sulu_route.route_generator')
            ->public();

        $services->set('sulu_route.route_loader')
            ->class(RouteCollectionForRequestRouteLoader::class)
            ->args([
                new Reference('sulu_route.route_repository'),
                tagged_locator('sulu_route.route_defaults_provider', 'resource_key', 'getResourceKey'),
                new Reference('router.request_context'),
            ])
            ->tag('sulu_route.route_collection_for_request_loader', ['priority' => 100]);

        $services->set('sulu_route.route_history_defaults_provider')
            ->class(RouteHistoryDefaultsProvider::class)
            ->args([
                new Reference('sulu_route.route_repository'),
                new Reference('sulu_route.route_generator'),
            ])
            ->tag('sulu_route.route_defaults_provider');

        $services->set('sulu_route.resource_locator_generator')
            ->class(ResourceLocatorGenerator::class)
            ->args([
                new Reference('sulu_route.route_repository'),
                new Reference('sulu_route.path_cleanup'),
            ]);

        $services->set('sulu_route.resource_locator_generate_controller')
            ->class(ResourceLocatorGenerateController::class)
            ->args([
                new Reference('sulu_route.resource_locator_generator'),
            ])
            ->tag('controller.service_arguments');

        $builder->registerForAutoconfiguration(SiteRouteGeneratorInterface::class)
            ->addTag('sulu_route.site_route_generator');

        $builder->registerForAutoconfiguration(RouteCollectionForRequestLoaderInterface::class)
            ->addTag('sulu_route.route_collection_for_request_loader');

        $builder->registerForAutoconfiguration(RouteDefaultsProviderInterface::class)
            ->addTag('sulu_route.route_defaults_provider');
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('doctrine')) {
            $builder->prependExtensionConfig(
                'doctrine',
                [
                    'orm' => [
                        'mappings' => [
                            'SuluRoute' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Route\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Route',
                                'alias' => 'SuluRoute',
                                'is_bundle' => false,
                                'mapping' => true,
                            ],
                        ],
                    ],
                ],
            );
        }

        if ($builder->hasExtension('cmf_routing')) {
            $builder->prependExtensionConfig(
                'cmf_routing',
                [
                    'chain' => [
                        'routers_by_id' => [
                            'router.default' => 100,
                            'cmf_routing.dynamic_router' => 20,
                        ],
                    ],
                    'dynamic' => [
                        ...($builder->hasExtension('sulu_route') ? [] : [ // TODO remove this check when `deprecated_service_bridge.xml` removed
                            'route_provider_service_id' => 'sulu_route.symfony_cmf_route_provider',
                        ]),
                        'enabled' => true,
                        'uri_filter_regexp' => '/^(?!\/admin\b).*/', // exclude admin routes
                    ],
                ]
            );

            if ($builder->hasExtension('sulu_route')) {
                $builder->prependExtensionConfig(
                    'cmf_routing',
                    [
                        'dynamic' => [
                            'route_provider_service_id' => 'sulu_route.symfony_cmf_route_provider',
                        ],
                    ]
                );
            }
        }
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }
}
