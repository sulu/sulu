<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\DispositionType;

use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Media\DispositionType\DispositionTypeService;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DispositionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $defaultDispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT;

    /**
     * @var DispositionTypeService
     */
    protected $dispositionType;

    protected function setUp()
    {
        $this->dispositionType = new DispositionTypeService(
            $this->defaultDispositionType,
            ['application/pdf', 'image/jpeg'],
            ['text/plain']
        );
    }

    public function testGetMimeTypeDisposition()
    {
        // Test MimeType defined as inline disposition
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->dispositionType->getMimeTypeDisposition('application/pdf')
        );

        // Test MimeType defined as attachment disposition
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->dispositionType->getMimeTypeDisposition('text/plain')
        );

        // Test MimeType that has no defined disposition - default disposition should be returned
        $this->assertEquals(
            $this->defaultDispositionType,
            $this->dispositionType->getMimeTypeDisposition('text/html')
        );
    }

    public function testGetFileVersionDisposition()
    {
        // Test FileVersion object with MimeType defined as inline disposition
        $fileVersion = $this->createFileVersionObject('foobar.pdf', 'application/pdf');
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->dispositionType->getFileVersionDisposition($fileVersion)
        );

        // Test FileVersion object with MimeType defined as attachment disposition
        $fileVersion = $this->createFileVersionObject('foobar.txt', 'text/plain');
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->dispositionType->getFileVersionDisposition($fileVersion)
        );

        // Test FileVersion object with MimeType that has no defined disposition
        $fileVersion = $this->createFileVersionObject('foobar.html', 'text/html');
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->dispositionType->getFileVersionDisposition($fileVersion)
        );
    }

    /**
     * @param string $name
     * @param string $mimeType
     * @param int $version
     *
     * @return FileVersion
     */
    protected function createFileVersionObject($name, $mimeType, $version = 1)
    {
        $fileVersion = new FileVersion();
        $fileVersion->setName($name)
            ->setMimeType($mimeType)
            ->setVersion($version);

        return $fileVersion;
    }
}
