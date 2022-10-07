<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\FormatCache\LocalFormatCache;
use Symfony\Component\Filesystem\Filesystem;

class LocalFormatCacheTest extends TestCase
{
    /**
     * @var LocalFormatCache
     */
    protected $localStorage;

    /**
     * @var int
     */
    protected $segments = 10;

    /**
     * setUp.
     */
    public function setUp(): void
    {
        $fileSystem = new Filesystem();
        $this->localStorage = new LocalFormatCache(
            $fileSystem,
            '/web/uploads/media',
            '/uploads/media/{slug}',
            $this->segments,
            [
                'sulu-50x50' => [
                    'name' => 'sulu-50x50',
                ],
            ]
        );
    }

    /**
     * testMediaUrlEncoding.
     */
    public function testMediaUrlEncoding(): void
    {
        $version = 2;
        $subVersion = 3;
        $fileId = 1;
        $format = 'sulu-50x50';
        $fileName = 'Test With Spaces & Co.jpg';
        $filePath = $this->localStorage->getMediaUrl($fileId, $fileName, $format, $version, $subVersion);

        $this->assertSame(
            '/uploads/media/sulu-50x50/01/1-Test%20With%20Spaces%20%26%20Co.jpg?v=2-3',
            $filePath
        );
    }
}
