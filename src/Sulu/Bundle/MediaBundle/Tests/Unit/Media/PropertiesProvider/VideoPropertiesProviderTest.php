<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\PropertiesProvider;

use FFMpeg\FFProbe;
use FFMpeg\FFProbe\DataMapping\Format;
use FFMpeg\FFProbe\DataMapping\Stream;
use FFMpeg\FFProbe\DataMapping\StreamCollection;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\VideoPropertiesProvider;
use Sulu\Bundle\MediaBundle\Tests\Functional\Traits\CreateUploadedFileTrait;

class VideoPropertiesProviderTest extends TestCase
{
    use ProphecyTrait;
    use CreateUploadedFileTrait;

    /**
     * @var ObjectProphecy<FFProbe>
     */
    private $ffprobe;

    /**
     * @var VideoPropertiesProvider
     */
    private $videoPropertiesProvider;

    protected function setUp(): void
    {
        $this->ffprobe = $this->prophesize(FFProbe::class);

        $this->videoPropertiesProvider = new VideoPropertiesProvider(
            $this->ffprobe->reveal()
        );
    }

    public function testProvideImage(): void
    {
        // prepare data
        $uploadedFile = $this->createUploadedFileImage();

        // test function
        $this->assertSame(
            [],
            $this->videoPropertiesProvider->provide($uploadedFile)
        );
    }

    public function testProvideVideo(): void
    {
        // prepare data
        $uploadedFile = $this->createUploadedFileVideo();

        // prepare expected service calls
        $format = new Format(['duration' => 40]);

        $this->ffprobe->format(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($format);

        $streamCollection = new StreamCollection([new Stream([
            'codec_type' => 'video',
            'width' => 360,
            'height' => 240,
        ])]);

        $this->ffprobe->streams(Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn($streamCollection);

        // test function
        $this->assertSame(
            [
                'duration' => 40,
                'width' => 360,
                'height' => 240,
            ],
            $this->videoPropertiesProvider->provide($uploadedFile)
        );
    }
}
