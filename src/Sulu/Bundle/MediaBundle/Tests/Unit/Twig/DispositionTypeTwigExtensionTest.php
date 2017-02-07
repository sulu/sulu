<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Twig;

use Sulu\Bundle\MediaBundle\Api\Media;

class DispositionTypeTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMediaUrlDefaultInline()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/pdf');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', ['application/pdf'], []);

        $result = $extension->getMediaUrl($mediaMock->reveal());
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlDefaultAttachment()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/pdf');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('attachment', ['application/pdf'], []);

        $result = $extension->getMediaUrl($mediaMock->reveal());
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlOtherMimeTypeDefaultInline()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/pdf');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', ['application/pdf'], []);

        $result = $extension->getMediaUrl($mediaMock->reveal());
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlOtherMimeTypeDefaultAttachment()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/html');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('attachment', ['application/pdf'], []);

        $result = $extension->getMediaUrl($mediaMock->reveal());
        $this->assertEquals('http://sulu.lo/media/1', $result);
    }

    public function testGetMediaUrlWithDispositionType()
    {
        $mediaMock = $this->prophesize(Media::class);
        $mediaMock->getMimeType()->willReturn('application/pdf');
        $mediaMock->getUrl()->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', ['application/pdf'], []);

        $result = $extension->getMediaUrl($mediaMock->reveal(), 'attachment');
        $this->assertEquals('http://sulu.lo/media/1', $result);
    }
}
