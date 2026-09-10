<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Domain\Exception;

use Sulu\Component\Rest\Exception\ExceptionResponseDataInterface;
use Sulu\Component\Rest\Exception\TranslationErrorMessagesExceptionInterface;

class WorkflowTransitionRequestPreValidationFailedException extends \RuntimeException implements TranslationErrorMessagesExceptionInterface, ExceptionResponseDataInterface
{
    public const EXCEPTION_CODE_PRE_VALIDATION_FAILED = 1108;

    /**
     * @param list<array{key: string, passed: bool, messages: list<array{key: string|null, parameters: array<string, float|int|string>, text: string|null}>}> $results in configuration order
     */
    public function __construct(
        private readonly array $results,
    ) {
        $failedKeys = [];
        foreach ($results as $result) {
            if (!$result['passed']) {
                $failedKeys[] = $result['key'];
            }
        }

        parent::__construct(
            'Content did not pass its pre-validators: ' . \implode(', ', $failedKeys),
            self::EXCEPTION_CODE_PRE_VALIDATION_FAILED,
        );
    }

    public function getMessageTranslations(): array
    {
        $translations = [];
        foreach ($this->results as $result) {
            foreach ($result['messages'] as $message) {
                if (null === $message['key']) {
                    continue;
                }

                $translations[] = ['key' => $message['key'], 'parameters' => $message['parameters']];
            }
        }

        return $translations;
    }

    public function getResponseData(): array
    {
        // Passed checks travel too, so the admin can list what is done beside what is left.
        return ['preValidationResults' => $this->results];
    }
}
