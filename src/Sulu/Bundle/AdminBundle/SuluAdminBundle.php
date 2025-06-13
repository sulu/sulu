<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddMetadataProviderPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ExposeResourceRoutesPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SuluAdminBundle extends Bundle
{
    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddAdminPass());
        $container->addCompilerPass(new AddMetadataProviderPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_admin.teaser_provider_pool',
                'sulu.teaser.provider',
                0,
                'alias'
            )
        );

        if ($container->hasExtension('fos_js_routing')) {
            $container->addCompilerPass(new ExposeResourceRoutesPass());
        }
    }
}
