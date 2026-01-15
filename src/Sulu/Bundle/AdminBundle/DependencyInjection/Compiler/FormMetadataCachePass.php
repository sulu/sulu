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

class FormMetadataCachePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getParameter('sulu_admin.forms.directories') as $directory) {
            $container->addResource(new DirectoryResource($directory, '/\.xml$/'));
        }
        foreach ($container->getParameter('sulu_admin.list.directories') as $directory) {
            $container->addResource(new DirectoryResource($directory, '/\.xml$/'));
        }
        $container->addResource(new DirectoryResource($container->getParameter('sulu_core.webspace.config_dir'), '/\.xml$/'));
    }
}
