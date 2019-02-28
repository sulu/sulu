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

class PreviewCacheItem
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * @var mixed
     */
    private $object;

    /**
     * @var string
     */
    private $html;

    public function __construct(string $id, string $locale, int $userId, string $providerKey, $object)
    {
        $this->id = $id;
        $this->locale = $locale;
        $this->userId = $userId;
        $this->providerKey = $providerKey;
        $this->object = $object;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLocale(): string
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
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $object
     */
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
        return md5(sprintf('%s.%s.%s.%s', $this->providerKey, $this->id, $this->locale, $this->userId));
    }
}
