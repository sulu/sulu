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

use Webmozart\Assert\Assert;

/**
 * @experimental
 */
class CreatePageMessage
{
    /**
     * @var string|null
     */
    private $uuid;

    /**
     * @param mixed[] $data
     */
    public function __construct(
        private string $webspaceKey,
        private string $parentId,
        private array $data
    ) {
        $uuid = $data['uuid'] ?? null;

        Assert::string($data['locale'] ?? null, \sprintf('Expected a "locale" string given. Got: %s', \gettype($data['locale'])));
        Assert::nullOrString($uuid, \sprintf('Expected a "uuid" string or null given. Got: %s', \gettype($uuid)));

        $this->uuid = $uuid;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }
}
