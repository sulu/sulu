<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\TypeManager;

use PHPUnit\Framework\Attributes\DataProvider;
use Sulu\Bundle\MediaBundle\Media\TypeManager\TypeManager;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class TypeManagerTest extends SuluTestCase
{
    private TypeManager $typeManager;

    /**
     * @var array<array{type: string, mimeTypes: array<string>}>
     */
    private array $mediaTypes = [
        [
            'type' => 'document',
            'mimeTypes' => ['*'],
        ],
        [
            'type' => 'image',
            'mimeTypes' => ['image/*'],
        ],
        [
            'type' => 'video',
            'mimeTypes' => ['video/*'],
        ],
        [
            'type' => 'audio',
            'mimeTypes' => ['audio/*'],
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->typeManager = new TypeManager($this->mediaTypes);
    }

    #[DataProvider('mediaTypesProvider')]
    public function testMediaTypes(string $mimeType, string $expectedMediaType): void
    {
        $computedType = $this->typeManager->getMediaType($mimeType);
        $this->assertEquals($expectedMediaType, $computedType);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function mediaTypesProvider(): array
    {
        return [
            // documents
            ['application/pdf', 'document'],
            ['application/msword', 'document'],
            ['application/vnd.ms-excel', 'document'],
            ['application/zip', 'document'],
            ['text/html', 'document'],
            // images
            ['image/jpg', 'image'],
            ['image/jepg', 'image'],
            ['image/gif', 'image'],
            ['image/png', 'image'],
            ['image/vnd.adobe.photoshop', 'image'],
            // videos
            ['video/mp4', 'video'],
            ['video/mov', 'video'],
            // audios
            ['audio/mpeg', 'audio'],
            ['audio/mp3', 'audio'],
        ];
    }
}
