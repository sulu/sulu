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

    public function testLoadNoConfig()
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'sulu.cache.warmer.structure', 'kernel.cache_warmer'
        );

        $this->assertEquals(
            'default',
            $this->container->getParameter('sulu.content.structure.default_type.page')
        );
        $this->assertEquals(
            'default',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );
    }

    public function testDefaults()
    {
        $this->load(array(
            'content' => array(
                'structure' => array(
                    'default_type' => array(
                        'page' => 'foobar',
                        'snippet' => 'barfoo',
                    ),
                    'paths' => array(),
                )
            )
        ));

        $this->assertContainerHasParameter(
            'foobar',
            $this->container->getParameter('sulu.content.structure.default_type.page')
        );
        $this->assertEquals(
            'barfoo',
            $this->container->getParameter('sulu.content.structure.default_type.snippet')
        );
    }
}
