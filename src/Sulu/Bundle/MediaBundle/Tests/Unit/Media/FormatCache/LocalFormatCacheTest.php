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

    private LocalFormatCache $localFormatCache;

    protected function setUp(): void
    {
        $fileSystem = new Filesystem();
        $this->localFormatCache = new LocalFormatCache(
            $fileSystem,
            '/web/uploads/media',
            '/uploads/media/{slug}',
            10,
        );
    }

    public function testAnalyzedMediaUrl(): void
    {
        $info = $this->localFormatCache->analyzedMediaUrl('/uploads/media/sulu-50x50/01/21-test-image.jpg');

        self::assertEquals([
            'id' => 21,
            'format' => 'sulu-50x50',
            'fileName' => 'test-image.jpg',
        ], $info);
    }

    public function testAnalyzedMediaUrlDecode(): void
    {
        $info = $this->localFormatCache->analyzedMediaUrl('/uploads/media/sulu-50x50/01/21-Test%20With%20Spaces%20%26%20Co.jpg');

        self::assertEquals([
            'id' => 21,
            'format' => 'sulu-50x50',
            'fileName' => 'Test With Spaces & Co.jpg',
        ], $info);
    }

    public function testMediaUrlEncoding(): void
    {
        $version = 2;
        $subVersion = 3;
        $fileId = 1;
        $format = 'sulu-50x50';
        $fileName = 'Test With Spaces & Co.jpg';
        $filePath = $this->localFormatCache->getMediaUrl($fileId, $fileName, $format, $version, $subVersion);

        $this->assertSame(
            '/uploads/media/sulu-50x50/01/1-Test%20With%20Spaces%20%26%20Co.jpg?v=2-3',
            $filePath
        );
    }
}
