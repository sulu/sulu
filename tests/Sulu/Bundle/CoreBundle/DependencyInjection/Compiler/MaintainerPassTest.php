<?php

namespace Sulu\Bundle\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\MaintainerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MaintainerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new MaintainerPass());
    }

    public function testPass()
    {
        $managerDefinition = new Definition();
        $this->setDefinition('sulu.maintainence_manager', $managerDefinition);

        $maintainerDefinition = new Definition();
        $maintainerDefinition->addTag('sulu.maintainer');
        $this->setDefinition('some_maintainer', $maintainerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu.maintainence_manager',
            'registerMaintainer',
            array(
                new Reference('some_maintainer')
            )
        );
    }
}
