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

use Sulu\Component\Rest\Exception\PreValidationFailedExceptionInterface;

class WorkflowTransitionRequestPreValidationFailedException extends \RuntimeException implements PreValidationFailedExceptionInterface
{
    public const ERROR_CODE = 1107;

    /**
     * @param list<array{key: string, passed: bool, failures: list<array{messageKey: string, messageParameters: array<string, float|int|string>}>}> $results in configuration order
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
            self::ERROR_CODE,
        );
    }

    public function getPreValidationResults(): array
    {
        return $this->results;
    }
}
