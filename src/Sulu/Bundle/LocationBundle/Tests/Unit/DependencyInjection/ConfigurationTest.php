<?php

namespace Sulu\Bundle\LocationBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Sulu\Bundle\LocationBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    protected $expectedDefaultConfig = array(
        'types' => array(
            'location' => array(
                'template' => 'SuluLocationBundle:Template:content-types/location.html.twig',
            ),
        ),
        'enabled_providers' => array('leaflet', 'google'),
        'default_provider' => 'leaflet',
        'providers' => array(
            'leaflet' => array(
                'title' => 'Leaflet (OSM)',
                'geolocation_service' => 'nominatim',
            ),
            'google' => array(
                'title' => 'Google Maps',
                'api_key' => '',
                'geolocation_service' => 'nominatim',
            ),
        )
    );

    public function getConfiguration()
    {
        return new Configuration();
    }

    public function testDefaultConfig()
    {
        $this->assertProcessedConfigurationEquals(array(), $this->expectedDefaultConfig);
    }

    public function testOverwriteConfig()
    {
        $expectedConfig = $this->expectedDefaultConfig;
        $expectedConfig['providers']['google']['title'] = 'My Maps';

        $this->assertProcessedConfigurationEquals(array(
            array(
                'providers' => array(
                    'google' => array(
                        'title' => 'My Maps',
                    ),
                ),
            ),
        ), $expectedConfig);
    }
}
