<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Domain\Event;

use Sulu\Component\Security\Authentication\UserInterface;

abstract class DomainEvent
{
    private \DateTimeImmutable $eventDateTime;

    private ?string $eventBatch = null;

    private ?UserInterface $user = null;

    public function __construct()
    {
        $this->eventDateTime = new \DateTimeImmutable();
    }

    abstract public function getEventType(): string;

    /**
     * @return mixed[]
     */
    public function getEventContext(): array
    {
        return [];
    }

    /**
     * @return mixed[]|null
     */
    public function getEventPayload(): ?array
    {
        return null;
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

    abstract public function getResourceKey(): string;

    abstract public function getResourceId(): string;

    /**
     * This method should return the locale of a resource, which is affected by the current event.
     * If all locales of a resource are effected by the current event, this method should return null.
     */
    public function getResourceLocale(): ?string
    {
        return null;
    }

    public function getResourceWebspaceKey(): ?string
    {
        return null;
    }

    public function getResourceTitle(): ?string
    {
        return null;
    }

    /**
     * This method should return the locale in which the resource title is stored.
     * If the resource title is not localized (e.g. a tag name), this method should return null.
     */
    public function getResourceTitleLocale(): ?string
    {
        return $this->getResourceLocale();
    }

    public function getResourceSecurityContext(): ?string
    {
        return null;
    }

    public function getResourceSecurityObjectType(): ?string
    {
        return null;
    }

    public function getResourceSecurityObjectId(): ?string
    {
        return $this->getResourceId();
    }
}
