<?php

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Sulu\Bundle\CoreBundle\DependencyInjection\SuluCoreExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class SuluCoreExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new SuluCoreExtension
        );
    }

    public function testLoad()
    {
        $this->load();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sulu.phpcr.wrapper',
            0,
            array(
                'PHPCR\SessionInterface' => 'Sulu\Component\PHPCR\Wrapper\Wrapped\Session',
                'PHPCR\NodeInterface' => 'Sulu\Component\PHPCR\Wrapper\Wrapped\Node',
                'PHPCR\PropertyInterface' => 'Sulu\Component\PHPCR\Wrapper\Wrapped\Property',
            )
        );
    }
}
