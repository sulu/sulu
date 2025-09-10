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
    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        // Message Handler services
        $services->set('sulu_search.reindex_message_handler')
            ->class(ReindexMessageHandler::class)
            ->args([
                new Reference('cmsig_seal.engine.default'),
                tagged_iterator('cmsig_seal.reindex_provider'),
            ])
            ->tag('messenger.message_handler');
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
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
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4); // target the root of the library where config, src, ... is located
    }
}
