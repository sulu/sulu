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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\MediaPropertiesProvider;
use Sulu\Bundle\MediaBundle\Media\PropertiesProvider\PropertiesProviderInterface;
use Sulu\Bundle\MediaBundle\Tests\Functional\Traits\CreateUploadedFileTrait;
use Symfony\Component\HttpFoundation\File\File;

class MediaPropertiesProviderTest extends TestCase
{
    use CreateUploadedFileTrait;

    public function testProvide(): void
    {
        $propertyProvider1 = $this->createVideoPropertiesProvider();
        $propertyProvider2 = $this->createImagePropertiesProvider();

        $mediaPropertiesProvider = new MediaPropertiesProvider([
            $propertyProvider1,
            $propertyProvider2,
        ]);

        $file = $this->createUploadedFileImage();

        $this->assertSame(
            ['width' => 1, 'height' => 1],
            $mediaPropertiesProvider->provide($file)
        );
    }

    private function createVideoPropertiesProvider(): PropertiesProviderInterface
    {
        return new class() implements PropertiesProviderInterface {
            public function provide(File $file): array
            {
                throw new \RuntimeException('Should not be called.');
            }

            public static function supports(File $file): bool
            {
                return \fnmatch('video/*', $file->getMimeType());
            }
        };
    }

    private function createImagePropertiesProvider(): PropertiesProviderInterface
    {
        return new class() implements PropertiesProviderInterface {
            public function provide(File $file): array
            {
                return ['width' => 1, 'height' => 1];
            }

            public static function supports(File $file): bool
            {
                return \fnmatch('image/*', $file->getMimeType());
            }
        };
    }
}
