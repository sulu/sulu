<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Application\Message;

class RestoreArticleVersionMessage
{
    /**
     * @param array{
     *     uuid?: string,
     * } $articleIdentifier
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $articleIdentifier,
        private int $version,
        private array $options = []
    ) {
    }

    /**
     * @return array{
     *     uuid?: string,
     * }
     */
    public function getArticleIdentifier(): array
    {
        return $this->articleIdentifier;
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
}
