<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\Message;

class RestoreContentVersionMessage
{
    /**
     * @param array{
     *     uuid?: string,
     * } $contentRichEntityIdentifier
     * @param array<string, mixed> $options
     */
    public function __construct(
        private array $contentRichEntityIdentifier,
        private int $version,
        private string $resourceKey,
        private array $options = []
    ) {
    }

    /**
     * @return array{
     *     uuid?: string,
     * }
     */
    public function getContentRichEntityIdentifier(): array
    {
        return $this->contentRichEntityIdentifier;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
