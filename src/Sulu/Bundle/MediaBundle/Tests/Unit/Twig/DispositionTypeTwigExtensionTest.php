<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Twig;

class DispositionTypeTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMediaUrlDefaultInline()
    {
        $mediaMock = $this->getMock('Sulu\Bundle\MediaBundle\Api\Media', array(), array(), '', false);
        $mediaMock->expects($this->any())->method('getMimeType')->willReturn('application/pdf');
        $mediaMock->expects($this->any())->method('getUrl')->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', array('application/pdf'), array());

        $result = $extension->getMediaUrl($mediaMock);
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlDefaultAttachment()
    {
        $mediaMock = $this->getMock('Sulu\Bundle\MediaBundle\Api\Media', array(), array(), '', false);
        $mediaMock->expects($this->any())->method('getMimeType')->willReturn('application/pdf');
        $mediaMock->expects($this->any())->method('getUrl')->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('attachment', array('application/pdf'), array());

        $result = $extension->getMediaUrl($mediaMock);
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlOtherMimeTypeDefaultInline()
    {
        $mediaMock = $this->getMock('Sulu\Bundle\MediaBundle\Api\Media', array(), array(), '', false);
        $mediaMock->expects($this->any())->method('getMimeType')->willReturn('application/html');
        $mediaMock->expects($this->any())->method('getUrl')->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', array('application/pdf'), array());

        $result = $extension->getMediaUrl($mediaMock);
        $this->assertEquals('http://sulu.lo/media/1?inline=1', $result);
    }

    public function testGetMediaUrlOtherMimeTypeDefaultAttachment()
    {
        $mediaMock = $this->getMock('Sulu\Bundle\MediaBundle\Api\Media', array(), array(), '', false);
        $mediaMock->expects($this->any())->method('getMimeType')->willReturn('application/html');
        $mediaMock->expects($this->any())->method('getUrl')->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('attachment', array('application/pdf'), array());

        $result = $extension->getMediaUrl($mediaMock);
        $this->assertEquals('http://sulu.lo/media/1', $result);
    }

    public function testGetMediaUrlWithDispositionType()
    {
        $mediaMock = $this->getMock('Sulu\Bundle\MediaBundle\Api\Media', array(), array(), '', false);
        $mediaMock->expects($this->any())->method('getMimeType')->willReturn('application/pdf');
        $mediaMock->expects($this->any())->method('getUrl')->willReturn('http://sulu.lo/media/1');

        $extension = new DispositionTypeTwigExtension('inline', array('application/pdf'), array());

        $result = $extension->getMediaUrl($mediaMock, 'attachment');
        $this->assertEquals('http://sulu.lo/media/1', $result);
    }
}
