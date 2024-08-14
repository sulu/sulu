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

namespace Sulu\CustomUrl\Application\Messages;

class RemoveCustomUrlMessage
{
    public function __construct(
        private string $uuid,
        private string $webspaceKey
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }
}
