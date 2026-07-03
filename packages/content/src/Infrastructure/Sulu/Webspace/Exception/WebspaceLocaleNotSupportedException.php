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

class WebspaceLocaleNotSupportedException extends UnprocessableEntityHttpException implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private readonly string $webspaceKey,
        private readonly string $locale,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf('Webspace "%s" does not support locale "%s"', $this->webspaceKey, $this->locale),
            $previous,
        );
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_content.webspace_does_not_support_locale';
    }

    /**
     * @return array<string, string>
     */
    public function getMessageTranslationParameters(): array
    {
        return [
            '{webspace}' => $this->webspaceKey,
            '{locale}' => $this->locale,
        ];
    }
}
