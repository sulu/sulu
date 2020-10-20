<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AuditBundle\Entity;

class Trail implements TrailInterface
{
    private $id;

    private $event;

    private $triggerId;

    private $targetId;

    private $targetClass;

    private $triggerClass;

    private $createdAt;

    private $changes;

    /**
     * Notification constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): void
    {
        $this->event = $event;
    }

    public function getTriggerId(): int
    {
        return $this->triggerId;
    }

    public function setTriggerId(int $triggerId): void
    {
        $this->triggerId = $triggerId;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    /**
     * @param int $targetId
     */
    public function setTargetId($targetId): void
    {
        $this->targetId = $targetId;
    }

    public function getTargetClass(): string
    {
        return $this->targetClass;
    }

    public function setTargetClass(string $targetClass): void
    {
        $this->targetClass = $targetClass;
    }

    public function getTriggerClass(): string
    {
        return $this->triggerClass;
    }

    public function setTriggerClass(string $triggerClass): void
    {
        $this->triggerClass = $triggerClass;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param mixed $changes
     */
    public function setChanges($changes): void
    {
        $this->changes = $changes;
    }
}
