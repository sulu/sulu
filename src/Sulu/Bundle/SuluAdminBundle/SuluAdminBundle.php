<?php

namespace Sulu\Bundle\AdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass;

class SuluAdminBundle extends Bundle
{
    public function build(ContainerBuilder $container) {
        parent::build($container);

        $container->addCompilerPass(new AddAdminPass);
    }
}
