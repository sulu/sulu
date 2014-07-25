<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddJsConfigPass;
use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\SuluVersionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SuluAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAdminPass);
        $container->addCompilerPass(new AddJsConfigPass());
        $container->addCompilerPass(new SuluVersionPass());
    }
}
