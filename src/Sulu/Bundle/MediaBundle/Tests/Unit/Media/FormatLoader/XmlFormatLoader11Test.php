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

class XmlFormatLoader11Test extends WebspaceTestCase
{
    use ProphecyTrait;

    /**
     * @var XmlFormatLoader11
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

        $this->loader = new XmlFormatLoader11($locator->reveal());
    }

    public function testSupports10(): void
    {
        $this->assertFalse(
            $this->loader->supports(\dirname(__DIR__) . '/../../Fixtures/image-formats/version10.xml')
        );
    }

    public function testSupports11(): void
    {
        $this->assertTrue(
            $this->loader->supports(\dirname(__DIR__) . '/../../Fixtures/image-formats/version11.xml')
        );
    }

    public function testLoad(): void
    {
        $result = $this->loader->load(\dirname(__DIR__) . '/../../Fixtures/image-formats/version11.xml');

        $this->assertEquals(4, \count($result));

        $this->assertArrayHasKey('400x400', $result);
        $this->assertEquals(
            [
                'key' => '400x400',
                'internal' => false,
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '400',
                    'y' => '400',
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
            $result['400x400']
        );
        $this->assertNotNull($result['400x400']['internal']);

        $this->assertArrayHasKey('400x-inset', $result);
        $this->assertEquals(
            [
                'key' => '400x-inset',
                'internal' => true,
                'meta' => [
                    'title' => [
                        'en' => 'My 400 Format',
                        'de' => 'Mein 400 Format',
                    ],
                ],
                'scale' => [
                    'x' => '400',
                    'y' => null,
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'forceRatio' => true,
                    'retina' => false,
                ],
                'transformations' => [],
                'options' => [
                    'jpeg_quality' => '70',
                ],
            ],
            $result['400x-inset']
        );
        $this->assertNotNull($result['400x-inset']['internal']);

        $this->assertArrayHasKey('200x180-inset', $result);
        $this->assertEquals(
            [
                'key' => '200x180-inset',
                'internal' => false,
                'meta' => [
                    'title' => [],
                ],
                'scale' => [
                    'x' => '200',
                    'y' => '180',
                    'mode' => ImageInterface::THUMBNAIL_INSET,
                    'forceRatio' => true,
                    'retina' => false,
                ],
                'transformations' => [],
                'options' => [],
            ],
            $result['200x180-inset']
        );
        $this->assertNotNull($result['200x180-inset']['internal']);

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

    public function testLoadWithMissingDimension(): void
    {
        $this->expectException(MissingScaleDimensionException::class);
        $this->loader->load(\dirname(__DIR__) . '/../../Fixtures/image-formats/version11_missing_dimension.xml');
    }
}
