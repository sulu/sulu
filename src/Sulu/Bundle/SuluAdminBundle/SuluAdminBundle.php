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

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddJsConfigPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;

class SuluAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddAdminPass);
        $container->addCompilerPass(new AddJsConfigPass());
    }
}
