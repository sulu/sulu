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

namespace Sulu\Redirect\Infrastructure\Symfony\HttpKernel;

use Sulu\Bundle\PersistenceBundle\DependencyInjection\PersistenceExtensionTrait;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Redirect\Domain\Model\Redirect;
use Sulu\Redirect\Domain\Model\RedirectInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluRedirectBundle extends AbstractBundle
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
                        ->arrayNode('redirect')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('model')->defaultValue(Redirect::class)->end()
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
                            'SuluRedirect' => [
                                'type' => 'xml',
                                'prefix' => 'Sulu\Redirect\Domain\Model',
                                'dir' => \dirname(__DIR__, 4) . '/config/doctrine/Redirect',
                                'alias' => 'SuluRedirect',
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
                        'Sulu\\Redirect\\Migrations' => \dirname(__DIR__, 4) . '/migrations',
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
            RedirectInterface::class => 'sulu.model.redirect.class',
        ], $container);
    }
}
