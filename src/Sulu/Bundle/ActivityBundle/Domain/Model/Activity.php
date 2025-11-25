<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Domain\Model;

use Sulu\Component\Security\Authentication\UserInterface;

class Activity implements ActivityInterface
{
    private int $id;

    private string $type;

    /**
     * @var mixed[]
     */
    private array $context = [];

    /**
     * @var mixed[]|null
     */
    private ?array $payload = null;

    private \DateTimeImmutable $timestamp;

    private ?string $batch = null;

    private ?UserInterface $user = null;

    private string $resourceKey;

    private string $resourceId;

    private ?string $resourceLocale = null;

    private ?string $resourceWebspaceKey = null;

    private ?string $resourceTitle = null;

    private ?string $resourceTitleLocale = null;

    private ?string $resourceSecurityContext = null;

    private ?string $resourceSecurityObjectType = null;

    private ?string $resourceSecurityObjectId = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTimeImmutable $timestamp): static
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getBatch(): ?string
    {
        return $this->batch;
    }

    public function setBatch(?string $batch): static
    {
        $this->batch = $batch;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): static
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): static
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceLocale(): ?string
    {
        return $this->resourceLocale;
    }

    public function setResourceLocale(?string $resourceLocale): static
    {
        $this->resourceLocale = $resourceLocale;

        return $this;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->resourceWebspaceKey;
    }

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): static
    {
        $this->resourceWebspaceKey = $resourceWebspaceKey;

        return $this;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(?string $resourceTitle): static
    {
        $this->resourceTitle = $resourceTitle;

        return $this;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->resourceTitleLocale;
    }

    public function setResourceTitleLocale(?string $resourceTitleLocale): static
    {
        $this->resourceTitleLocale = $resourceTitleLocale;

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): static
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return $this->resourceSecurityObjectType;
    }

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): static
    {
        $this->resourceSecurityObjectType = $resourceSecurityObjectType;

        return $this;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->resourceSecurityObjectId;
    }

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): static
    {
        $this->resourceSecurityObjectId = $resourceSecurityObjectId;

        return $this;
    }
}
