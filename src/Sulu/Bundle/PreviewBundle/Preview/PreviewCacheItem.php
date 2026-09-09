<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

/**
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
class PreviewCacheItem
{
    /**
     * @var string
     */
    private $html;

    /**
     * @param array<string, mixed> $object
     * @param array<string, mixed> $data the values the form last sent, replayed onto fresh defaults when the item is read back
     * @param array<string, mixed> $context the context the form last sent, e.g. the selected template
     */
    public function __construct(
        private string $id,
        private ?string $locale,
        private int $userId,
        private string $providerKey,
        private array $object,
        private array $data = [],
        private array $context = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getObject(): array
    {
        return $this->object;
    }

    /**
     * @param array<string, mixed> $object
     */
    public function setObject(array $object): void
    {
        $this->object = $object;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function getHtml(): ?string
    {
        return $this->html;
    }

    public function setHtml(string $html): void
    {
        $this->html = $html;
    }

    public function getToken(): string
    {
        return \md5(\sprintf('%s.%s.%s', $this->providerKey, $this->id, $this->userId));
    }
}
