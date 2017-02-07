<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddJsConfigPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\ContentNavigationPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\WidgetsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAdminPass());
        $container->addCompilerPass(new AddJsConfigPass());
        $container->addCompilerPass(new ContentNavigationPass());
        $container->addCompilerPass(new WidgetsPass());
    }
}
