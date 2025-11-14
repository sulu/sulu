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

namespace Sulu\Search\Infrastructure\Symfony\HttpKernel;

use Sulu\Search\Application\MessageHandler\ReindexMessageHandler;
use Sulu\Search\Infrastructure\Sulu\Admin\SearchAdmin;
use Sulu\Search\UserInterface\Controller\Admin\SearchController;
use Sulu\Search\UserInterface\Controller\Website\SearchController as WebsiteSearchController;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 *
 * @codeCoverageIgnore
 */
final class SuluSearchBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('resources')
                        ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('icon')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('route')
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('resultToRouteName')
                                    ->useAttributeAsKey('name')
                                    ->scalarPrototype()->end()
                                ->end()
                                ->arrayNode('resultToRoute')
                                    ->useAttributeAsKey('name')
                                    ->scalarPrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->scalarNode('securityContext')
                    ->defaultNull()
                ->end()
                ->arrayNode('contexts')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
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
        $services = $container->services();

        /** @var array<string, array{resources?: string}> $admin */
        $admin = $config['admin'] ?? [];

        $builder->setParameter('sulu_search.admin_resources', $admin['resources'] ?? []);

        // Message Handler services
        $services->set('sulu_search.reindex_message_handler')
            ->class(ReindexMessageHandler::class)
            ->args([
                new Reference('cmsig_seal.engine.default'),
                tagged_iterator('cmsig_seal.reindex_provider'),
            ])
            ->tag('messenger.message_handler');

        // Sulu Integration service
        $services->set('sulu_search.search_admin')
            ->class(SearchAdmin::class)
            ->args([
                new Reference('sulu_admin.view_builder_factory'),
            ])
            ->tag('sulu.context', ['context' => 'admin'])
            ->tag('sulu.admin');

        // Controllers services
        $services->set('sulu_search.admin_search_controller')
            ->class(SearchController::class)
            ->public()
            ->args([
                new Reference('cmsig_seal.engine.default'),
                new Reference('sulu_core.list_rest_helper'),
                param('sulu_search.admin_resources'),
                new Reference('sulu_media.media_manager'),
                new Reference('security.token_storage'),
            ])
            ->tag('sulu.context', ['context' => 'admin']);

        $services->set('sulu_search.controller.website_search')
            ->class(WebsiteSearchController::class)
            ->public()
            ->args([
                new Reference('cmsig_seal.engine.default'),
                new Reference('sulu_core.webspace.request_analyzer'),
                new Reference('twig'),
                new Reference('sulu_website.resolver.template_attribute'),
            ])
            ->tag('sulu.context', ['context' => 'website']);
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
                    'resources' => [
                        'search' => [
                            'routes' => [
                                'list' => 'sulu_search.search',
                            ],
                        ],
                        'search_resources' => [
                            'routes' => [
                                'list' => 'sulu_search.resources',
                            ],
                        ],
                    ],
                ]
            );
        }

        if ($builder->hasExtension('cmsig_seal')) {
            $builder->prependExtensionConfig(
                'cmsig_seal',
                [
                    'schemas' => [
                        'sulu_search' => [
                            'dir' => \dirname(__DIR__, 4) . '/config/schemas',
                            'engine' => 'default',
                        ],
                    ],
                ],
            );
        }

        if (!$builder->hasParameter('sulu_search.admin_resources')) {
            $builder->setParameter('sulu_search.admin_resources', []);
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
