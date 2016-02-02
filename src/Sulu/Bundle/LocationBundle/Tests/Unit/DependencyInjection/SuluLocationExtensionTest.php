<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Sulu\Bundle\LocationBundle\DependencyInjection\SuluLocationExtension;

class SuluLocationExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions()
    {
        return [
            new SuluLocationExtension(),
        ];
    }

    public function testDefaultConfig()
    {
        $this->load();
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'registerProvider', [
                'google',
                [
                    'title' => 'Google Maps',
                    'api_key' => null,
                ],
            ]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'registerProvider', [
                'leaflet',
                [
                    'title' => 'Leaflet (OSM)',
                ],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'sulu_location.map_manager',
            'setDefaultProviderName', [
                'leaflet',
            ]
        );
    }
}
