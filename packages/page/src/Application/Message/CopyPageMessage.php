<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Application\Message;

class CopyPageMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $sourceIdentifier
     * @param array{
     *     uuid?: string
     * } $targetParentIdentifier
     */
    public function __construct(
        private array $sourceIdentifier,
        private array $targetParentIdentifier,
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

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getTargetParentIdentifier(): array
    {
        return $this->targetParentIdentifier;
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
