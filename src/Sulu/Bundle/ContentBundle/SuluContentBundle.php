<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle;

use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\ContentExportCompilerPass;
use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\SecurityPass;
use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\SmartContentDataProviderCompilerPass;
use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\StructureExtensionCompilerPass;
use Sulu\Bundle\ContentBundle\DependencyInjection\Compiler\WebspacesPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluContentBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ContentExportCompilerPass());
        $container->addCompilerPass(new SecurityPass());
        $container->addCompilerPass(new SmartContentDataProviderCompilerPass());
        $container->addCompilerPass(new WebspacesPass());
        $container->addCompilerPass(new StructureExtensionCompilerPass());
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_content.teaser.provider_pool',
                'sulu.teaser.provider',
                0,
                'alias'
            )
        );
        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass(
                'sulu_content.link_tag.provider_pool',
                'sulu.link.provider',
                0,
                'alias'
            )
        );
    }
}
