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

namespace Sulu\Content\Infrastructure\Symfony\HttpKernel;

use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverMetadataAwareInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Domain\Exception\ShadowSourceNotPublishedException;
use Sulu\Content\Infrastructure\Doctrine\EventListener\RouteCleanupListener;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ExcerptFormPass;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\ResourceLoaderCacheCompilerPass;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\SeoFormPass;
use Sulu\Content\Infrastructure\Symfony\HttpKernel\Compiler\SettingsFormPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluContentBundle extends AbstractBundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('content_resolver')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('max_depth')->defaultValue(5)->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $loader = new PhpFileLoader($builder, new FileLocator(\dirname(__DIR__, 4) . '/config'));
        $loader->load('data-mapper.php');
        $loader->load('merger.php');
        $loader->load('normalizer.php');
        $loader->load('services.php');
        $loader->load('form-visitor.php');
        $loader->load('controller.php');
        $loader->load('resolvers.php');
        $loader->load('resource-loader.php');
        $loader->load('reference.php');

        $services = $container->services();

        /** @var array{max_depth: int} $contentResolverConfig */
        $contentResolverConfig = $config['content_resolver'];

        $builder->getDefinition('sulu_content.content_resolver')
            ->setArgument('$maxDepth', $contentResolverConfig['max_depth']);

        $services->set('sulu_content.doctrine_route_cleanup_listener')
            ->class(RouteCleanupListener::class)
            ->tag('doctrine.event_listener', ['event' => 'preRemove', 'method' => 'preRemove'])
            ->tag('doctrine.event_listener', ['event' => 'postFlush', 'method' => 'postFlush'])
            ->tag('doctrine.event_listener', ['event' => 'onClear', 'method' => 'onClear'])
            ->tag('kernel.reset', ['method' => 'reset']);
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
                    'forms' => [
                        'directories' => [
                            \dirname(__DIR__, 4) . '/config/forms',
                        ],
                    ],
                ]
            );
        }

        if ($builder->hasExtension('fos_rest')) {
            $builder->prependExtensionConfig(
                'fos_rest',
                [
                    'exception' => [
                        'codes' => [
                            ShadowSourceNotPublishedException::class => 400,
                        ],
                    ],
                ]
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
        $container->addCompilerPass(new SettingsFormPass());
        $container->addCompilerPass(new ExcerptFormPass());
        $container->addCompilerPass(new SeoFormPass());
        $container->addCompilerPass(new ResourceLoaderCacheCompilerPass());

        $container->registerForAutoconfiguration(ResourceLoaderInterface::class)
            ->addTag('sulu_content.resource_loader');

        $container->registerForAutoconfiguration(PropertyResolverInterface::class)
            ->addTag('sulu_content.property_resolver');

        // ensure metadata-aware property resolvers are also tagged
        $container->registerForAutoconfiguration(PropertyResolverMetadataAwareInterface::class)
            ->addTag('sulu_content.property_resolver');
    }
}
