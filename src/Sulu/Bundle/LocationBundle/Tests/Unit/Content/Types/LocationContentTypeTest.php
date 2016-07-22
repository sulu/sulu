<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\LocationBundle\Tests\Unit\Content\Types;

use Sulu\Bundle\LocationBundle\Content\Types\LocationContentType;
use Sulu\Component\Content\Compat\PropertyParameter;

class LocationContentTypeTest extends \PHPUnit_Framework_TestCase
{
    protected $nodeRepository;
    protected $locationContent;
    protected $mapManager;

    public function setUp()
    {
        $this->nodeRepository = $this->getMock('Sulu\Bundle\ContentBundle\Repository\NodeRepositoryInterface');
        $this->phpcrNode = $this->getMock('PHPCR\NodeInterface');
        $this->suluProperty = $this->getMock('Sulu\Component\Content\Compat\PropertyInterface');
        $this->mapManager = $this->getMock('Sulu\Bundle\LocationBundle\Map\MapManager');
        $this->locationContent = new LocationContentType(
            $this->nodeRepository,
            'Foo:bar.html.twig',
            $this->mapManager,
            'some_geolocator'
        );
    }

    protected function initReadTest($data)
    {
        $this->suluProperty->expects($this->once())
            ->method('setValue')
            ->with($data);
    }

    public function provideRead()
    {
        return [
            [
                ['foo_bar' => 'bar_foo'],
            ],
        ];
    }

    /**
     * @dataProvider provideRead
     */
    public function testRead($data)
    {
        $this->initReadTest($data);

        $this->phpcrNode->expects($this->once())
            ->method('getPropertyValueWithDefault')
            ->with('foobar', '{}')
            ->will($this->returnValue(json_encode($data)));

        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('foobar'));

        $this->locationContent->read(
            $this->phpcrNode,
            $this->suluProperty,
            'webspace_key',
            'fr',
            'segment'
        );
    }

    /**
     * @dataProvider provideRead
     */
    public function testWrite($data)
    {
        $this->suluProperty->expects($this->once())
            ->method('getName')
            ->willReturn('myname');

        $this->suluProperty->expects($this->once())
            ->method('getValue')
            ->willReturn($data);

        $this->phpcrNode->expects($this->once())
            ->method('setProperty')
            ->with('myname', json_encode($data));

        $this->locationContent->write(
            $this->phpcrNode,
            $this->suluProperty,
            1,
            'webspace_key',
            'fr',
            'segment'
        );
    }

    public function testGetParams()
    {
        $expected = [
            'countries' => new PropertyParameter(
                'countries',
                [
                    'at' => new PropertyParameter('at', 'Austria'),
                    'fr' => new PropertyParameter('fr', 'France'),
                    'ch' => new PropertyParameter('ch', 'Switzerland'),
                    'de' => new PropertyParameter('de', 'Germany'),
                    'gb' => new PropertyParameter('gb', 'United Kingdom'),
                ],
                'collection'
            ),
            'mapProviders' => new PropertyParameter(
                'mapProviders',
                [
                    'foo' => 'Foo',
                    'bar' => 'Bar',
                ],
                'collection'
            ),
            'defaultProvider' => new PropertyParameter('defaultProvider', 'leaflet'),
            'geolocatorName' => new PropertyParameter('geolocatorName', 'some_geolocator'),
        ];

        $this->mapManager->expects($this->once())
            ->method('getProvidersAsArray')
            ->will($this->returnValue($expected['mapProviders']->getValue()));

        $this->mapManager->expects($this->once())
            ->method('getDefaultProviderName')
            ->will($this->returnValue('leaflet'));

        $params = $this->locationContent->getDefaultParams();

        $this->assertEquals($expected['mapProviders'], $params['mapProviders']);
        $this->assertEquals($expected['defaultProvider'], $params['defaultProvider']);
        $this->assertEquals($expected['geolocatorName'], $params['geolocatorName']);
        foreach ($expected['countries']->getValue() as $parameter) {
            $this->assertEquals($parameter, $params['countries']->getValue()[$parameter->getName()]);
        }
    }
}
