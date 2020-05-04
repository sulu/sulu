<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\DependencyInjection;

use Sulu\Bundle\WebsiteBundle\Controller\DefaultController;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SuluWebsiteExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('twig') && class_exists(ExceptionController::class)) {
            $container->prependExtensionConfig('twig', [
                'exception_controller' => null,
            ]);
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig(
                'sulu_admin',
                [
                    'lists' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/lists',
                        ],
                    ],
                    'forms' => [
                        'directories' => [
                            __DIR__ . '/../Resources/config/forms',
                        ],
                    ],
                    'resources' => [
                        'analytics' => [
                            'routes' => [
                                'list' => 'sulu_website.cget_webspace_analytics',
                                'detail' => 'sulu_website.get_webspace_analytics',
                            ],
                        ],
                    ],
                ]
            );
        }

        if (SuluKernel::CONTEXT_WEBSITE !== $container->getParameter('sulu.context')) {
            return;
        }

        $container->prependExtensionConfig('cmf_routing', [
            'chain' => [
                'routers_by_id' => [
                    'router.default' => 100,
                    'cmf_routing.dynamic_router' => 20,
                ],
            ],
            'dynamic' => [
                'enabled' => true,
                'route_provider_service_id' => 'sulu_website.provider.content',
            ],
        ]);

        $container->prependExtensionConfig('fos_rest', [
            'exception' => [
                'enabled' => false,
            ],
        ]);
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'sulu_website.navigation.cache.lifetime',
            $config['twig']['navigation']['cache_lifetime']
        );
        $container->setParameter(
            'sulu_website.content.cache.lifetime',
            $config['twig']['content']['cache_lifetime']
        );
        $container->setParameter(
            'sulu_website.sitemap.cache.lifetime',
            $config['twig']['content']['cache_lifetime']
        );
        $container->setParameter(
            'sulu_website.sitemap.dump_dir',
            $config['sitemap']['dump_dir']
        );
        $container->registerForAutoconfiguration(SitemapProviderInterface::class)
            ->addTag('sulu.sitemap.provider');

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('sitemap.xml');
        $loader->load('command.xml');

        if ($config['analytics']['enabled']) {
            $loader->load('analytics.xml');
        }

        if (SuluKernel::CONTEXT_WEBSITE == $container->getParameter('sulu.context')) {
            $loader->load('website.xml');

            // default local provider
            $container->setAlias('sulu_website.default_locale.provider', $config['default_locale']['provider_service_id']);

            // add alias for default controller
            $container->setAlias(DefaultController::class, 'sulu_website.default_controller')
                ->setPublic(true);

            if (class_exists(ExceptionController::class)) {
                $loader->load('exception_controller.xml');
            }
        }
    }
}
