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

class EventRecord implements EventRecordInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var mixed[]
     */
    private $eventContext = [];

    /**
     * @var mixed[]|null
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

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): EventRecordInterface
    {
        $this->eventType = $eventType;

        return $this;
    }

    public function getEventContext(): array
    {
        return $this->eventContext;
    }

    public function setEventContext(array $eventContext): EventRecordInterface
    {
        $this->eventContext = $eventContext;

        return $this;
    }

    public function getEventPayload(): ?array
    {
        return $this->eventPayload;
    }

    public function setEventPayload(?array $eventPayload): EventRecordInterface
    {
        $this->eventPayload = $eventPayload;

        return $this;
    }

    public function getEventDateTime(): \DateTimeImmutable
    {
        return $this->eventDateTime;
    }

    public function setEventDateTime(\DateTimeImmutable $eventDateTime): EventRecordInterface
    {
        $this->eventDateTime = $eventDateTime;

        return $this;
    }

    public function getEventBatch(): ?string
    {
        return $this->eventBatch;
    }

    public function setEventBatch(?string $eventBatch): EventRecordInterface
    {
        $this->eventBatch = $eventBatch;

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): EventRecordInterface
    {
        $this->user = $user;

        return $this;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): EventRecordInterface
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): EventRecordInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getResourceLocale(): ?string
    {
        return $this->resourceLocale;
    }

    public function setResourceLocale(?string $resourceLocale): EventRecordInterface
    {
        $this->resourceLocale = $resourceLocale;

        return $this;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return $this->resourceWebspaceKey;
    }

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): EventRecordInterface
    {
        $this->resourceWebspaceKey = $resourceWebspaceKey;

        return $this;
    }

    public function getResourceTitle(): ?string
    {
        return $this->resourceTitle;
    }

    public function setResourceTitle(?string $resourceTitle): EventRecordInterface
    {
        $this->resourceTitle = $resourceTitle;

        return $this;
    }

    public function getResourceTitleLocale(): ?string
    {
        return $this->resourceTitleLocale;
    }

    public function setResourceTitleLocale(?string $resourceTitleLocale): EventRecordInterface
    {
        $this->resourceTitleLocale = $resourceTitleLocale;

        return $this;
    }

    public function getResourceSecurityContext(): ?string
    {
        return $this->resourceSecurityContext;
    }

    public function setResourceSecurityContext(?string $resourceSecurityContext): EventRecordInterface
    {
        $this->resourceSecurityContext = $resourceSecurityContext;

        return $this;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return $this->resourceSecurityObjectType;
    }

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): EventRecordInterface
    {
        $this->resourceSecurityObjectType = $resourceSecurityObjectType;

        return $this;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->resourceSecurityObjectId;
    }

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): EventRecordInterface
    {
        $this->resourceSecurityObjectId = $resourceSecurityObjectId;

        return $this;
    }
}
