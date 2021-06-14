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
                ['id' => 3, 'resourceKey' => 'media'],
            ],
            [
                ['id' => 3, 'resourceKey' => 'collections'],
                ['id' => 2, 'resourceKey' => 'media'],
            ],
            [
                ['id' => 2, 'resourceKey' => 'collections'],
                ['id' => 1, 'resourceKey' => 'media'],
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
        $this->assertSame(12345, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalChildResources(), $result['totalChildResources']);
        $this->assertEquals($exception->getChildResources(), $result['childResources']);
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
                ['id' => 3, 'resourceKey' => 'media'],
            ],
            [
                ['id' => 3, 'resourceKey' => 'collections'],
                ['id' => 2, 'resourceKey' => 'media'],
            ],
            [
                ['id' => 2, 'resourceKey' => 'collections'],
                ['id' => 1, 'resourceKey' => 'media'],
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
        $this->assertSame(12345, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalChildResources(), $result['totalChildResources']);
        $this->assertEquals($exception->getChildResources(), $result['childResources']);
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
            ['id' => 2, 'resourceKey' => 'collections', 'title' => 'Collection 2'],
            ['id' => 3, 'resourceKey' => 'collections', 'title' => 'Collection 3'],
            ['id' => 4, 'resourceKey' => 'collections', 'title' => 'Collection 4'],
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
        $this->assertSame(12346, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalUnauthorizedChildResources(), $result['totalUnauthorizedChildResources']);
        $this->assertEquals($exception->getUnauthorizedChildResources(), $result['unauthorizedChildResources']);
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
            ['id' => 2, 'resourceKey' => 'collections', 'title' => 'Collection 2'],
            ['id' => 3, 'resourceKey' => 'collections', 'title' => 'Collection 3'],
            ['id' => 4, 'resourceKey' => 'collections', 'title' => 'Collection 4'],
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
        $this->assertSame(12346, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getTotalUnauthorizedChildResources(), $result['totalUnauthorizedChildResources']);
        $this->assertEquals($exception->getUnauthorizedChildResources(), $result['unauthorizedChildResources']);
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
