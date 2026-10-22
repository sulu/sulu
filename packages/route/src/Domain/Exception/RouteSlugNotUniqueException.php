<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Domain\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * The slug is already used by the route or the route history of another resource.
 */
class RouteSlugNotUniqueException extends \Exception implements TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private string $slug,
        private string $locale,
        private string $webspace,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf(
                'The slug "%s" is already used by another resource in webspace "%s" and locale "%s".',
                $this->slug,
                $this->webspace,
                $this->locale,
            ),
            0,
            $previous,
        );
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getWebspace(): string
    {
        return $this->webspace;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_route.slug_not_unique';
    }

    /**
     * @return array<string, string>
     */
    public function getMessageTranslationParameters(): array
    {
        return [
            '{slug}' => $this->slug,
        ];
    }
}
