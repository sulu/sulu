<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\DispositionType;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\DispositionType\DispositionTypeResolver;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DispositionTypeResolverTest extends TestCase
{
    /**
     * @var string
     */
    protected $defaultDispositionType = ResponseHeaderBag::DISPOSITION_ATTACHMENT;

    /**
     * @var DispositionTypeResolver
     */
    protected $dispositionType;

    protected function setUp(): void
    {
        $this->dispositionType = new DispositionTypeResolver(
            $this->defaultDispositionType,
            ['application/pdf', 'image/jpeg'],
            ['text/plain']
        );
    }

    public function testGetMimeTypeDisposition(): void
    {
        // Test MimeType defined as inline disposition
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $this->dispositionType->getByMimeType('application/pdf')
        );

        // Test MimeType defined as attachment disposition
        $this->assertEquals(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->dispositionType->getByMimeType('text/plain')
        );

        // Test MimeType that has no defined disposition - default disposition should be returned
        $this->assertEquals(
            $this->defaultDispositionType,
            $this->dispositionType->getByMimeType('text/html')
        );
    }
}
