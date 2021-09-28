<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Domain\Model;

class PreviewLink implements PreviewLinkInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $resourceId;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var array<string, mixed>
     */
    private $options;

    /**
     * @var int
     */
    private $visitCount = 0;

    /**
     * @var \DateTimeImmutable|null
     */
    private $lastVisit;

    public function __construct(string $token, string $resourceKey, string $resourceId, string $locale, array $options)
    {
        $this->token = $token;
        $this->resourceKey = $resourceKey;
        $this->resourceId = $resourceId;
        $this->locale = $locale;
        $this->options = $options;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getVisitCount(): int
    {
        return $this->visitCount;
    }

    public function increaseVisitCount(): PreviewLinkInterface
    {
        ++$this->visitCount;
        $this->lastVisit = new \DateTimeImmutable();

        return $this;
    }

    public function getLastVisit(): ?\DateTimeImmutable
    {
        return $this->lastVisit;
    }
}
