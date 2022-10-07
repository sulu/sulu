<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Api\Media;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DispositionTypeTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function testGetMediaUrlWithoutForceDispositionType(): void
    {
        $this->assertEquals(
            'http://sulu.lo/media/1',
            $this->getExtensionGetMediaUrlResult($this->getMediaMock()->reveal())
        );
    }

    public function testGetMediaUrlWithForceInlineDispositionType(): void
    {
        $this->assertEquals(
            'http://sulu.lo/media/1?inline=1',
            $this->getExtensionGetMediaUrlResult($this->getMediaMock()->reveal(), ResponseHeaderBag::DISPOSITION_INLINE)
        );
    }

    public function testGetMediaUrlWithForceAttachmentDispositionType(): void
    {
        $this->assertEquals(
            'http://sulu.lo/media/1?inline=0',
            $this->getExtensionGetMediaUrlResult($this->getMediaMock()->reveal(), ResponseHeaderBag::DISPOSITION_ATTACHMENT)
        );
    }

    public function testGetMediaUrlWithForceWrongDispositionType(): void
    {
        $this->assertEquals(
            'http://sulu.lo/media/1',
            $this->getExtensionGetMediaUrlResult($this->getMediaMock()->reveal(), 'foobar')
        );
    }

    /**
     * @return ObjectProphecy
     */
    protected function getMediaMock()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/pdf');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        return $mediaMock;
    }

    /**
     * @param string $dispositionType
     *
     * @return string
     */
    protected function getExtensionGetMediaUrlResult(Media $media, $dispositionType = null)
    {
        $extension = new DispositionTypeTwigExtension();

        return $extension->getMediaUrl($media, $dispositionType);
    }
}
