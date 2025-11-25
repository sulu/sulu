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

use Webmozart\Assert\Assert;

class CreateSnippetMessage
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    private ?string $uuid = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $uuid = $data['uuid'] ?? null;

        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
        Assert::nullOrString($uuid, 'Expected "uuid" to be a string.');

        $this->data = $data;
        $this->uuid = $uuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
