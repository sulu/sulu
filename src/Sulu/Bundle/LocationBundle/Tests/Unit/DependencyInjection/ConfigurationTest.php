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

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Sulu\Bundle\LocationBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use ConfigurationTestCaseTrait;

    protected $expectedDefaultConfig = [
        'types' => [
            'location' => [
                'template' => 'SuluLocationBundle:Template:content-types/location.html.twig',
            ],
        ],
        'enabled_providers' => ['leaflet', 'google'],
        'default_provider' => 'leaflet',
        'geolocator' => 'nominatim',
        'providers' => [
            'leaflet' => [
                'title' => 'Leaflet (OSM)',
            ],
            'google' => [
                'title' => 'Google Maps',
                'api_key' => '',
            ],
        ],
        'geolocators' => [
            'nominatim' => [
                'endpoint' => 'http://open.mapquestapi.com/nominatim/v1/search.php',
            ],
            'google' => [
                'api_key' => '',
            ],
        ],
    ];

    public function getConfiguration()
    {
        return new Configuration();
    }

    public function testDefaultConfig()
    {
        $this->assertProcessedConfigurationEquals([], $this->expectedDefaultConfig);
    }

    public function testOverwriteConfig()
    {
        $expectedConfig = $this->expectedDefaultConfig;
        $expectedConfig['providers']['google']['title'] = 'My Maps';

        $this->assertProcessedConfigurationEquals([
            [
                'providers' => [
                    'google' => [
                        'title' => 'My Maps',
                    ],
                ],
            ],
        ], $expectedConfig);
    }
}
