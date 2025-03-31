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
     * @param mixed $object
     */
    public function __construct(
        private string $id,
        private ?string $locale,
        private int $userId,
        private string $providerKey,
        private $object,
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

    public function getObject()
    {
        return $this->object;
    }

    public function setObject($object): void
    {
        $this->object = $object;
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
