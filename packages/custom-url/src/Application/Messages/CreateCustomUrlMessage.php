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

use Webmozart\Assert\Assert;

class CreateCustomUrlMessage
{
    private ?string $uuid = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private string $webspaceKey,
        private array $data,
    ) {
        $uuid = $data['uuid'] ?? null;

        Assert::nullOrString($uuid, \sprintf('Expected a "uuid" string or null given. Got: %s', \gettype($uuid)));

        $this->uuid = $uuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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
