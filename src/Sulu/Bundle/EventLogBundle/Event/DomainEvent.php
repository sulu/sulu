<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Event;

use Sulu\Component\Security\Authentication\UserInterface;

class DomainEvent
{
    /**
     * @var string
     */
    private $eventType;

    /**
     * @var array
     */
    private $eventPayload;

    /**
     * @var \DateTimeImmutable
     */
    private $eventDateTime;

    /**
     * @var string|null
     */
    private $eventBatch;

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
    private $resourceTitle;

    /**
     * @var string|null
     */
    private $resourceSecurityContext;

    /**
     * @var string|null
     */
    private $resourceSecurityType;

    /**
     * Constructor.
     */
    public function __construct(
        string $eventType,
        string $resourceKey,
        string $resourceId,
        ?string $resourceLocale = null,
        ?string $resourceTitle = null,
        ?string $resourceSecurityContext = null,
        ?string $resourceSecurityType = null,
        array $eventPayload = []
    ) {
        $this->eventType = $eventType;
        $this->resourceKey = $resourceKey;
        $this->resourceId = $resourceId;
        $this->resourceLocale = $resourceLocale;
        $this->resourceTitle = $resourceTitle;
        $this->resourceSecurityContext = $resourceSecurityContext;
        $this->resourceSecurityType = $resourceSecurityType;
        $this->eventPayload = $eventPayload;

        $this->eventDateTime = new \DateTimeImmutable();
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): DomainEvent
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getEventPayload(): array
    {
        return $this->eventPayload;
    }

    public function setEventPayload(array $eventPayload): DomainEvent
    {
        $this->eventPayload = $eventPayload;

        return $this;
    }

    public function getEventDateTime(): \DateTimeImmutable
    {
        return $this->eventDateTime;
    }

    public function setEventDateTime(\DateTimeImmutable $eventDateTime): DomainEvent
    {
        $this->eventDateTime = $eventDateTime;

        return $this;
    }

    public function getEventBatch(): ?string
    {
        return $this->eventBatch;
    }

    public function setEventBatch(?string $eventBatch): DomainEvent
    {
        $this->eventBatch = $eventBatch;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): DomainEvent
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): DomainEvent
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): DomainEvent
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceLocale(): ?string
    {
        return $this->resourceLocale;
    }

    public function setResourceLocale(?string $resourceLocale): DomainEvent
    {
        $this->resourceLocale = $resourceLocale;

        return $this;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(?string $resourceTitle): DomainEvent
    {
        $this->resourceTitle = $resourceTitle;

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): DomainEvent
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityType(): ?string
    {
        return $this->resourceSecurityType;
    }

    public function setResourceSecurityType(?string $resourceSecurityType): DomainEvent
    {
        $this->resourceSecurityType = $resourceSecurityType;

        return $this;
    }
}
