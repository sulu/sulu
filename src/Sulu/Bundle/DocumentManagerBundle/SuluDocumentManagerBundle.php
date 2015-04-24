<?php

namespace Sulu\Bundle\DocumentManagerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sulu\Bundle\DocumentManagerBundle\DependencyInjection\Compiler\InitializerPass;

class SuluDocumentManagerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new InitializerPass());
    }
}
