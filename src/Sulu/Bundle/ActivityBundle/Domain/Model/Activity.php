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

use DateTimeImmutable;
use Sulu\Component\Security\Authentication\UserInterface;

class Activity implements ActivityInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var mixed[]
     */
    private $context = [];

    /**
     * @var mixed[]|null
     */
    private $payload;

    /**
     * @var DateTimeImmutable
     */
    private $timestamp;

    /**
     * @var string|null
     */
    private $batch;

    /**
     * @var UserInterface|null
     */
    private $user;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $resourceId;

    /**
     * @var string|null
     */
    private $resourceLocale;

    /**
     * @var string|null
     */
    private $resourceWebspaceKey;

    /**
     * @var string|null
     */
    private $resourceTitle;

    /**
     * @var string|null
     */
    private $resourceTitleLocale;

    /**
     * @var string|null
     */
    private $resourceSecurityContext;

    /**
     * @var string|null
     */
    private $resourceSecurityObjectType;

    /**
     * @var string|null
     */
    private $resourceSecurityObjectId;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): ActivityInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): ActivityInterface
    {
        $this->context = $context;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): ActivityInterface
    {
        $this->payload = $payload;

        return $this;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function setTimestamp(DateTimeImmutable $timestamp): ActivityInterface
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function getBatch(): ?string
    {
        return $this->batch;
    }

    public function setBatch(?string $batch): ActivityInterface
    {
        $this->batch = $batch;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): ActivityInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): ActivityInterface
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): ActivityInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceLocale(): ?string
    {
        return $this->resourceLocale;
    }

    public function setResourceLocale(?string $resourceLocale): ActivityInterface
    {
        $this->resourceLocale = $resourceLocale;

        return $this;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->resourceWebspaceKey;
    }

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): ActivityInterface
    {
        $this->resourceWebspaceKey = $resourceWebspaceKey;

        return $this;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(?string $resourceTitle): ActivityInterface
    {
        $this->resourceTitle = $resourceTitle;

        return $this;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->resourceTitleLocale;
    }

    public function setResourceTitleLocale(?string $resourceTitleLocale): ActivityInterface
    {
        $this->resourceTitleLocale = $resourceTitleLocale;

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): ActivityInterface
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return $this->resourceSecurityObjectType;
    }

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): ActivityInterface
    {
        $this->resourceSecurityObjectType = $resourceSecurityObjectType;

        return $this;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->resourceSecurityObjectId;
    }

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): ActivityInterface
    {
        $this->resourceSecurityObjectId = $resourceSecurityObjectId;

        return $this;
    }
}
