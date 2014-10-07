<?php

namespace Sulu\Bundle\CoreBundle\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class SuluCoreExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new SuluCoreExtension()
        );
    }

    public function testServices()
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'sulu.cache.warmer.structure', 'kernel.cache_warmer'
        );
    }
}
