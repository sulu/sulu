<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatLoader;

use Imagine\Image\ImageInterface;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\FormatLoader\Exception\MissingScaleDimensionException;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class XmlFormatLoader10Test extends WebspaceTestCase
{
    use ProphecyTrait;

    /**
     * @var XmlFormatLoader10
     */
    protected $loader;

    public function setUp(): void
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(
            function($arguments) {
                return $arguments[0];
            }
        );

        $this->loader = new XmlFormatLoader10($locator->reveal());
    }

    public function testSupports10(): void
    {
        $this->assertTrue(
            $this->loader->supports(\dirname(__DIR__) . '/../../Fixtures/image-formats/version10.xml')
        );
    }

    public function testSupports11(): void
    {
        $this->assertFalse(
            $this->loader->supports(\dirname(__DIR__) . '/../../Fixtures/image-formats/version11.xml')
        );
    }

    public function testLoadDeprecated(): void
    {
        $result = $this->loader->load(\dirname(__DIR__) . '/../../Fixtures/image-formats/version10.xml');

        $this->assertEquals(3, \count($result));

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
                    'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
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
                    'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
                    'forceRatio' => true,
                    'retina' => false,
                ],
                'transformations' => [],
                'options' => [],
            ],
            $result['300x']
        );
        $this->assertNotNull($result['300x']['internal']);

        $this->assertArrayHasKey('3840x2160-retina', $result);
        $this->assertEquals(
            [
                'key' => '3840x2160-retina',
                'internal' => false,
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '3840',
                    'y' => '2160',
                    'mode' => ImageInterface::THUMBNAIL_OUTBOUND,
                    'forceRatio' => true,
                    'retina' => true,
                ],
                'transformations' => [],
                'options' => [],
            ],
            $result['3840x2160-retina']
        );
        $this->assertNotNull($result['3840x2160-retina']['internal']);
    }

    public function testLoadDeprecatedWithMissingDimension(): void
    {
        $this->expectException(MissingScaleDimensionException::class);
        $this->loader->load(\dirname(__DIR__) . '/../../Fixtures/image-formats/version10_missing_dimension.xml');
    }
}
