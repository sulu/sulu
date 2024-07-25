<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatCache;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Filesystem\Filesystem;

class LocalFormatCacheTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<Filesystem> */
    private ?ObjectProphecy $filesystem = null;

    private ?LocalFormatCache $localFormatCache = null;

    protected function setUp(): void
    {
        $this->filesystem = $this->prophesize(Filesystem::class);
        $this->localFormatCache = new LocalFormatCache(
            $this->filesystem->reveal(),
            '/',
            '/',
            10
        );
    }

    public function testAnalyzedMediaUrl(): void
    {
        $info = $this->localFormatCache?->analyzedMediaUrl('/uploads/media/950x/01/21-test-image.jpg');

        self::assertEquals([
            'id' => 21,
            'format' => '950x',
            'fileName' => 'test-image.jpg',
        ], $info);
    }

    public function testAnalyzedMediaUrlWithWhiteSpace(): void
    {
        $info = $this->localFormatCache?->analyzedMediaUrl('/uploads/media/950x/01/21-test%20image.jpg');

        self::assertEquals([
            'id' => 21,
            'format' => '950x',
            'fileName' => 'test image.jpg',
        ], $info);
    }
}
