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

namespace Sulu\Content\Infrastructure\Sulu\Webspace\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class WebspaceNotFoundException extends UnprocessableEntityHttpException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private readonly string $webspaceKey,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf('Webspace "%s" not found', $this->webspaceKey),
            $previous,
        );
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.webspace_not_found';
    }

    /**
     * @return array<string, string>
     */
    public function getMessageTranslationParameters(): array
    {
        return [
            '{webspace}' => $this->webspaceKey,
        ];
    }
}
