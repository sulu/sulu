<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Rest\Exception\ExceptionResponseDataInterface;
use Sulu\Component\Rest\Exception\ReferencingResourcesFoundException;
use Sulu\Component\Rest\Exception\RemoveDependantResourcesFoundException;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Sulu\Component\Rest\Exception\TranslationErrorMessagesExceptionInterface;
use Sulu\Component\Rest\FlattenExceptionNormalizer;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlattenExceptionNormalizerTest extends TestCase
{
    use ProphecyTrait;

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
                return 'sulu_security.key_assigned_to_other_role';
            }

            /**
             * @return array<string, string>
             */
            public function getMessageTranslationParameters(): array
            {
                return [
                    '{key}' => 'role_key',
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
            'sulu_security.key_assigned_to_other_role',
            [
                '{key}' => 'role_key',
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

    public function testNormalizeDependantResourcesFoundExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::cetera())
            ->will(function($args) {
                return $args[0];
            });

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new RemoveDependantResourcesFoundException(
            ['id' => 1, 'resourceKey' => 'collections'],
            [
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
            ],
            5
        );

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
        $this->assertSame(1105, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getDependantResourcesCount(), $result['dependantResourcesCount']);
        $this->assertEquals($exception->getDependantResourceBatches(), $result['dependantResourceBatches']);
        $this->assertEquals($exception->getResource(), $result['resource']);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeDependantResourcesFoundExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);
        $translator->trans(Argument::cetera())
            ->will(function($args) {
                return $args[0];
            });

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new RemoveDependantResourcesFoundException(
            ['id' => 1, 'resourceKey' => 'collections'],
            [
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
            ],
            5
        );

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
        $this->assertSame(1105, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getDependantResourcesCount(), $result['dependantResourcesCount']);
        $this->assertEquals($exception->getDependantResourceBatches(), $result['dependantResourceBatches']);
        $this->assertEquals($exception->getResource(), $result['resource']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testNormalizeReferencingResourcesFoundExceptionDebugFalse(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new ReferencingResourcesFoundException(
            ['id' => 1, 'resourceKey' => 'snippets'],
            [
                ['id' => 2, 'resourceKey' => 'snippets', 'title' => 'Foo'],
                ['id' => 3, 'resourceKey' => 'snippets', 'title' => 'Bar'],
                ['id' => 4, 'resourceKey' => 'snippets', 'title' => 'Baz'],
            ],
            3
        );

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
        $this->assertSame(1106, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getReferencingResourcesCount(), $result['referencingResourcesCount']);
        $this->assertEquals($exception->getResource(), $result['resource']);
        $this->assertEquals($exception->getReferencingResources(), $result['referencingResources']);
        $this->assertArrayNotHasKey('errors', $result);
    }

    public function testNormalizeReferencingResourcesFoundExceptionDebugTrue(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new ReferencingResourcesFoundException(
            ['id' => 1, 'resourceKey' => 'snippets'],
            [
                ['id' => 2, 'resourceKey' => 'snippets', 'title' => 'Foo'],
                ['id' => 3, 'resourceKey' => 'snippets', 'title' => 'Bar'],
                ['id' => 4, 'resourceKey' => 'snippets', 'title' => 'Baz'],
            ],
            3
        );

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
        $this->assertSame(1106, $result['code']);
        $this->assertSame($exception->getMessage(), $result['message']);
        $this->assertSame($exception->getReferencingResourcesCount(), $result['referencingResourcesCount']);
        $this->assertEquals($exception->getResource(), $result['resource']);
        $this->assertEquals($exception->getReferencingResources(), $result['referencingResources']);
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
                return 'sulu_security.key_assigned_to_other_role';
            }

            /**
             * @return array<string, string>
             */
            public function getMessageTranslationParameters(): array
            {
                return [
                    '{key}' => 'role_key',
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
            'sulu_security.key_assigned_to_other_role',
            [
                '{key}' => 'role_key',
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

    public function testNormalizeTranslationErrorMessagesExceptionJoinsThemIntoDetail(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Two rules failed', 1108) extends \Exception implements TranslationErrorMessagesExceptionInterface {
            public function getMessageTranslations(): array
            {
                return [
                    ['key' => 'sulu_content.workflow_transition_request.seo_required.missing', 'parameters' => ['fields' => 'title']],
                    ['key' => 'sulu_content.workflow_transition_request.excerpt_required.missing', 'parameters' => []],
                ];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn(['code' => 422, 'message' => 'Unprocessable Entity']);

        $translator->trans('sulu_content.workflow_transition_request.seo_required.missing', ['{fields}' => 'title'], 'admin')
            ->willReturn('SEO fields are still missing: title.');
        $translator->trans('sulu_content.workflow_transition_request.excerpt_required.missing', [], 'admin')
            ->willReturn('Excerpt fields are still missing.');

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(1108, $result['code']);
        $this->assertSame(
            'SEO fields are still missing: title. Excerpt fields are still missing.',
            $result['detail'],
            'Clients that read only `detail` still get every open point in one line.'
        );
    }

    public function testNormalizeExceptionResponseDataIsMergedIntoTheBody(): void
    {
        $decoratedNormalizer = $this->prophesize(NormalizerInterface::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $normalizer = new FlattenExceptionNormalizer(
            $decoratedNormalizer->reveal(),
            $translator->reveal()
        );

        $exception = new class('Rules ran', 1108) extends \Exception implements ExceptionResponseDataInterface {
            public function getResponseData(): array
            {
                return ['preValidationResults' => [['key' => 'seo_required', 'passed' => false]]];
            }
        };

        $flattenException = FlattenException::createFromThrowable($exception);

        $decoratedNormalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        )->willReturn(['code' => 422, 'message' => 'Unprocessable Entity']);

        $result = $normalizer->normalize(
            $flattenException,
            'json',
            ['exception' => $exception, 'debug' => false]
        );

        $this->assertSame(
            [['key' => 'seo_required', 'passed' => false]],
            $result['preValidationResults'],
            'The keys travel to the frontend as they are.'
        );
        $this->assertSame('Unprocessable Entity', $result['message']);
    }
}
