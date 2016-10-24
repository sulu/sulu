<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;

use Prophecy\Argument;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class XmlFormatLoader10Test extends WebspaceTestCase
{
    /**
     * @var XmlFormatLoader10
     */
    protected $loader;

    public function setUp()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(
            function ($arguments) {
                return $arguments[0];
            }
        );

        $this->loader = new XmlFormatLoader10($locator->reveal());
    }

    public function testSupports10()
    {
        $this->assertTrue(
            $this->loader->supports(dirname(__DIR__) . '/../../Fixtures/image/formats/version10.xml')
        );
    }

    public function testSupports11()
    {
        $this->assertFalse(
            $this->loader->supports(dirname(__DIR__) . '/../../Fixtures/image/formats/version11.xml')
        );
    }

    public function testLoadDeprecated()
    {
        $result = $this->loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/version10.xml');

        $this->assertEquals(2, count($result));

        $this->assertArrayHasKey('640x480', $result);
        $this->assertEquals(
            [
                'key' => '640x480',
                'internal' => false,
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '640',
                    'y' => '480',
                    'mode' => 'outbound',
                    'forceRatio' => false,
                    'retina' => false,
                ],
                'transformations' => [
                    [
                        'effect' => 'blur',
                        'parameters' => [
                            'type' => 'gaussian',
                            'kernel' => '20',
                        ],
                    ],
                ],
                'options' => [
                    'jpeg_quality' => '70',
                    'png_compression_level' => '6',
                ],
            ],
            $result['640x480']
        );
        $this->assertNotNull($result['640x480']['internal']);

        $this->assertArrayHasKey('300x', $result);
        $this->assertEquals(
            [
                'key' => '300x',
                'internal' => false,
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '300',
                    'y' => null,
                    'mode' => 'outbound',
                    'forceRatio' => true,
                    'retina' => false,
                ],
                'transformations' => [],
                'options' => [],
            ],
            $result['300x']
        );
        $this->assertNotNull($result['300x']['internal']);
    }

    /**
     * @expectedException Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\MissingScaleDimensionException
     */
    public function testLoadDeprecatedWithMissingDimension()
    {
        $this->loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/version10_missing_dimension.xml');
    }
}
