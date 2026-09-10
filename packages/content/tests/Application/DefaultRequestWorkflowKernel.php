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

namespace Sulu\Content\Tests\Application;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The one scenario a template tag cannot express: a `default` workflow that covers a resource key
 * and therefore applies to every template that carries no tag of its own.
 */
final class DefaultRequestWorkflowKernel extends Kernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        parent::registerContainerConfiguration($loader);

        $loader->load(static function(ContainerBuilder $container): void {
            $container->prependExtensionConfig('sulu_content', [
                'request_workflows' => [
                    'default' => [
                        'resources' => ['examples'],
                    ],
                ],
            ]);
        });
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir() . '_default_request_workflow';
    }
}
