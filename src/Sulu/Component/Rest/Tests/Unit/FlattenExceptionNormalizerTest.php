<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit\ListBuilder\Filter;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Exception\DeletionImpossibleChildPermissionsException;
use Sulu\Bundle\AdminBundle\Exception\DeletionImpossibleChildrenException;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Sulu\Component\Rest\FlattenExceptionNormalizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlattenExceptionNormalizerTest extends TestCase
{
    public function testNormalizeGeneralExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new \Exception('An unexpected error happened', 12345);
        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertSame(12345, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertArrayNotHasKey('detail', $result);
        $this->assertArrayHasKey('errors', $result);

        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Exception: An unexpected error happened in', $result['errors'][0]);
    }

    public function testNormalizeGeneralExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new \Exception('An unexpected error happened', 12345);
        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(12345, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertArrayNotHasKey('detail', $result);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeTranslationErrorMessageExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Key already exists', 56789) extends \Exception implements TranslationErrorMessageExceptionInterface {
            public function getMessageTranslationKey(): string
            {
                return 'error_message_translation_key';
            }

            /**
             * @return array<string, string>
             */
            public function getMessageTranslationParameters(): array
            {
                return [
                    'parameter1' => 'value1',
                    'parameter2' => 'value2',
                ];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $translator->trans(
            'error_message_translation_key',
            [
                'parameter1' => 'value1',
                'parameter2' => 'value2',
            ],
            'admin'
        )->willReturn('Translated Error Message Example');

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertSame(56789, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertSame('Translated Error Message Example', $result['detail']);
        $this->assertArrayHasKey('errors', $result);

        $this->assertIsArray($result['errors']);
        $this->assertCount(1, $result['errors']);
        $this->assertStringContainsString('Key already exists in', $result['errors'][0]);
    }

    public function testNormalizeDeletionImpossibleChildrenExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new DeletionImpossibleChildrenException([
            [
                ['id' => 3, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
            [
                ['id' => 3, 'resourceKey' => CollectionInterface::RESOURCE_KEY],
                ['id' => 2, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
            [
                ['id' => 2, 'resourceKey' => CollectionInterface::RESOURCE_KEY],
                ['id' => 1, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
        ], 5);

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 409,
            'message' => $exception->getMessage(),
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertIsArray($result);
        $this->assertSame(DeletionImpossibleChildrenException::EXCEPTION_CODE, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalChildren(), $result['totalChildren']);
        $this->assertEquals($exception->getChildResources(), $result['children']);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeDeletionImpossibleChildrenExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new DeletionImpossibleChildrenException([
            [
                ['id' => 3, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
            [
                ['id' => 3, 'resourceKey' => CollectionInterface::RESOURCE_KEY],
                ['id' => 2, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
            [
                ['id' => 2, 'resourceKey' => CollectionInterface::RESOURCE_KEY],
                ['id' => 1, 'resourceKey' => MediaInterface::RESOURCE_KEY],
            ],
        ], 5);

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 409,
            'message' => $exception->getMessage(),
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertIsArray($result);
        $this->assertSame(DeletionImpossibleChildrenException::EXCEPTION_CODE, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalChildren(), $result['totalChildren']);
        $this->assertEquals($exception->getChildResources(), $result['children']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testNormalizeDeletionImpossibleChildPermissionsExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new DeletionImpossibleChildPermissionsException([
            ['id' => 2, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 2'],
            ['id' => 3, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 3'],
            ['id' => 4, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 4'],
        ], 4);

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 403,
            'message' => $exception->getMessage(),
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertIsArray($result);
        $this->assertSame(DeletionImpossibleChildPermissionsException::EXCEPTION_CODE, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalUnauthorizedChildren(), $result['totalUnauthorizedChildren']);
        $this->assertEquals($exception->getUnauthorizedChildResources(), $result['unauthorizedChildren']);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeDeletionImpossibleChildPermissionsExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new DeletionImpossibleChildPermissionsException([
            ['id' => 2, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 2'],
            ['id' => 3, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 3'],
            ['id' => 4, 'resourceKey' => CollectionInterface::RESOURCE_KEY, 'title' => 'Collection 4'],
        ], 4);

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        )->willReturn([
            'code' => 403,
            'message' => $exception->getMessage(),
        ]);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => true]
        );

        $this->assertIsArray($result);
        $this->assertSame(DeletionImpossibleChildPermissionsException::EXCEPTION_CODE, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalUnauthorizedChildren(), $result['totalUnauthorizedChildren']);
        $this->assertEquals($exception->getUnauthorizedChildResources(), $result['unauthorizedChildren']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testNormalizeTranslationErrorMessageExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Key already exists', 56789) extends \Exception implements TranslationErrorMessageExceptionInterface {
            public function getMessageTranslationKey(): string
            {
                return 'error_message_translation_key';
            }

            /**
             * @return array<string, string>
             */
            public function getMessageTranslationParameters(): array
            {
                return [
                    'parameter1' => 'value1',
                    'parameter2' => 'value2',
                ];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn([
            'code' => 409,
            'message' => 'Conflict',
        ]);

        $translator->trans(
            'error_message_translation_key',
            [
                'parameter1' => 'value1',
                'parameter2' => 'value2',
            ],
            'admin'
        )->willReturn('Translated Error Message Example');

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(56789, $result['code']);
        $this->assertSame('Conflict', $result['message']);
        $this->assertSame('Translated Error Message Example', $result['detail']);
        $this->assertArrayNotHasKey('errors', $result);
    }
}
