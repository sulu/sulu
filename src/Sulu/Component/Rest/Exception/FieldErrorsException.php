<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Rejects submitted form data with a message for each invalid field.
 *
 * @experimental
 */
class FieldErrorsException extends UnprocessableEntityHttpException implements FieldErrorsExceptionInterface
{
    public const EXCEPTION_CODE_FIELD_ERRORS = 1109;

    /**
     * @param list<array{path: string, title: string}|array{path: string, key: string, parameters?: array<string, float|int|string>}> $fieldErrors
     */
    public function __construct(private readonly array $fieldErrors, ?\Throwable $previous = null)
    {
        parent::__construct('Invalid form data submitted', $previous, self::EXCEPTION_CODE_FIELD_ERRORS);
    }

    /**
     * The violation messages are used as they are, a validator has translated them already.
     */
    public static function fromConstraintViolationList(ConstraintViolationListInterface $violations): self
    {
        $fieldErrors = [];
        foreach ($violations as $violation) {
            $fieldErrors[] = [
                'path' => self::toPath($violation->getPropertyPath()),
                'title' => (string) $violation->getMessage(),
            ];
        }

        return new self($fieldErrors);
    }

    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }

    /**
     * Turns the property path of a violation (`blocks[0].key`) into a path of the form data (`blocks/0/key`).
     */
    private static function toPath(string $propertyPath): string
    {
        return \ltrim(\str_replace(['[', ']', '.'], ['/', '', '/'], $propertyPath), '/');
    }
}
