<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\CustomUrl\Application\Messages;

class CreateCustomUrlMessage
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(
        private string $webspaceKey,
        private array $data
    ) {
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
