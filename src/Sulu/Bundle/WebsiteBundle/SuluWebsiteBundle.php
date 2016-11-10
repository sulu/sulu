<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle;

use Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler\DeregisterDefaultRouteListenerCompilerPass;
use Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass;
use Sulu\Component\Util\SuluVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluWebsiteBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SuluVersionPass());
        $container->addCompilerPass(new DeregisterDefaultRouteListenerCompilerPass());

        $container->addCompilerPass(
            new TaggedServiceCollectorCompilerPass('sulu_website.sitemap.pool', 'sulu.sitemap.provider', 0, 'alias')
        );
    }
}
