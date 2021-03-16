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

abstract class DomainEvent
{
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

    public function __construct()
    {
        $this->eventDateTime = new \DateTimeImmutable();
    }

    abstract public function getEventType(): string;

    public function getEventPayload(): array
    {
        return [];
    }

    abstract public function getResourceKey(): string;

    abstract public function getResourceId(): string;

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

    public function getResourceSecurityContext(): ?string
    {
        return null;
    }

    public function getResourceSecurityType(): ?string
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
}
