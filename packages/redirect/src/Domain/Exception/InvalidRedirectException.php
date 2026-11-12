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

namespace Sulu\Redirect\Domain\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

class InvalidRedirectException extends \InvalidArgumentException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $reason,
    ) {
        parent::__construct(\sprintf('Invalid redirect "%s": %s', $field, $reason));
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_redirect.invalid_redirect';
    }

    public function getMessageTranslationParameters(): array
    {
        return [
            '{field}' => $this->field,
            '{reason}' => $this->reason,
        ];
    }
}
