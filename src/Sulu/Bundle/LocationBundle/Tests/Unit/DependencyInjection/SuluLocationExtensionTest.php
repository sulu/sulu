<?php

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\LocationBundle\DependencyInjection\SuluLocationExtension;

class SuluLocationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return array(
            new SuluLocationExtension(),
        );
    }

    public function testDefaultConfig()
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'registerProvider', array(
                'google',
                array(
                    'title' => 'Google Maps',
                    'api_key' => null,
                ),
            )
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'registerProvider', array(
                'leaflet',
                array(
                    'title' => 'Leaflet (OSM)',
                ),
            )
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'setDefaultProviderName', array(
                'leaflet',
            )
        );
    }
}
