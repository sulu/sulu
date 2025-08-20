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

class OrderPageMessage
{
    /**
     * @param array{
     *     uuid?: string
     * } $identifier
     */
    public function __construct(
        private array $identifier,
        private int $position,
        private string $locale,
    ) {
    }

    /**
     * @return array{
     *     uuid?: string
     * }
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }
}
