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

namespace Sulu\Bundle\AdminBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal no backwards compatibility promise, only for internal use.
 */
class FormMetadataCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getParameter('sulu_admin.forms.directories') as $directory) {
            $this->addDirectory($directory, $container);
        }
        foreach ($container->getParameter('sulu_admin.lists.directories') as $directory) {
            $this->addDirectory($directory, $container);
        }

        $this->addDirectory($container->getParameter('sulu_core.webspace.config_dir'), $container);

        // Adding templates to the cache
        foreach ($container->getParameter('sulu_admin.templates.configuration') as $configuration) {
            foreach ($configuration['directories'] as $directory) {
                $this->addDirectory($directory, $container);
            }
        }
    }

    private function addDirectory(string $directory, ContainerBuilder $container): void
    {
        // Resolving container parameters
        $directory = $container->resolveEnvPlaceholders(
            \preg_replace_callback(
                '#%([^%]+)%#',
                static function(array $match) use ($container): string {
                    /** @var string $param */
                    $param = $container->getParameter($match[1]);

                    return $param;
                },
                $directory
            )
        );

        if (!\is_string($directory)) {
            return;
        }

        if (!\file_exists($directory) || !\is_dir($directory)) {
            return;
        }

        $container->addResource(new DirectoryResource($directory, '/\.xml$/'));
    }
}
