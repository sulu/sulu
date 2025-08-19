<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Application\Message;

class RestoreSnippetVersionMessage
{
    /**
     * @param array{
     *     uuid?: string,
     * } $snippetIdentifier
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $snippetIdentifier,
        private int $version,
        private string $locale,
        private array $options = []
    ) {
    }

    /**
     * @return array{
     *     uuid?: string,
     * }
     */
    public function getSnippetIdentifier(): array
    {
        return $this->snippetIdentifier;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
