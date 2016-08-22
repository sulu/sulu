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
use Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\MissingScaleDimensionException;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class XmlFormatLoader11Test extends WebspaceTestCase
{
    /**
     * @var XmlFormatLoader11
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

        $this->loader = new XmlFormatLoader11($locator->reveal());
    }

    public function testSupports10()
    {
        $this->assertFalse(
            $this->loader->supports(dirname(__DIR__) . '/../../Fixtures/image/formats/deprecated.xml')
        );
    }

    public function testSupports11()
    {
        $this->assertTrue(
            $this->loader->supports(dirname(__DIR__) . '/../../Fixtures/image/formats/current.xml')
        );
    }

    public function testLoadDeprecated()
    {
        $result = $this->loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/current.xml');

        $this->assertEquals(3, count($result));

        $this->assertArrayHasKey('400x400', $result);
        $this->assertEquals(
            [
                'key' => '400x400',
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '400',
                    'y' => '400',
                    'mode' => 'outbound',
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
            $result['400x400']
        );

        $this->assertArrayHasKey('400x-inset', $result);
        $this->assertEquals(
            [
                'key' => '400x-inset',
                'meta' => [
                    'title' => [
                        'en' => 'My 400 Format',
                        'de' => 'Mein 400 Format',
                    ],
                ],
                'scale' => [
                    'x' => '400',
                    'y' => null,
                    'mode' => 'outbound',
                ],
                'transformations' => [],
                'options' => [
                    'jpeg_quality' => '70',
                ],
            ],
            $result['400x-inset']
        );

        $this->assertArrayHasKey('200x180-inset', $result);
        $this->assertEquals(
            [
                'key' => '200x180-inset',
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '200',
                    'y' => '180',
                    'mode' => 'outbound',
                ],
                'transformations' => [],
                'options' => [],
            ],
            $result['200x180-inset']
        );
    }

    public function testLoadDeprecatedWithMissingDimension()
    {
        $this->expectException(MissingScaleDimensionException::class);
        $this->loader->load(dirname(__DIR__) . '/../../Fixtures/image/formats/current_missing_dimension.xml');
    }
}
