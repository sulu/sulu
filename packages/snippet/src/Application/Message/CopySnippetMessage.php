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

class CopySnippetMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $sourceIdentifier
     */
    public function __construct(
        private array $sourceIdentifier,
        private string $locale,
        private ?string $targetUuid = null,
    ) {
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getSourceIdentifier(): array
    {
        return $this->sourceIdentifier;
    }

    public function getTargetUuid(): ?string
    {
        return $this->targetUuid;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
