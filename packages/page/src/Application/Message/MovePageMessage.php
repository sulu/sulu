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

class MovePageMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     * @param array{
     *     uuid?: string
     * } $targetParentIdentifier
     */
    public function __construct(
        private array $identifier,
        private array $targetParentIdentifier,
        private string $locale,
    ) {
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getIdentifier(): array
    {
        return $this->identifier;
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

    public function getLocale(): string
    {
        return $this->locale;
    }
}
