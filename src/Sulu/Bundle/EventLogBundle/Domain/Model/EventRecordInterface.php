<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Domain\Model;

use Sulu\Component\Security\Authentication\UserInterface;

interface EventRecordInterface
{
    public function getEventType(): string;

    public function setEventType(string $eventType): EventRecordInterface;

    /**
     * @return mixed[]
     */
    public function getEventContext(): array;

    /**
     * @param mixed[] $eventContext
     */
    public function setEventContext(array $eventContext): EventRecordInterface;

    /**
     * @return mixed[]|null
     */
    public function getEventPayload(): ?array;

    /**
     * @param mixed[]|null $eventPayload
     */
    public function setEventPayload(?array $eventPayload): EventRecordInterface;

    public function getEventDateTime(): \DateTimeImmutable;

    public function setEventDateTime(\DateTimeImmutable $eventDateTime): EventRecordInterface;

    public function getEventBatch(): ?string;

    public function setEventBatch(?string $eventBatch): EventRecordInterface;

    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): EventRecordInterface;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): EventRecordInterface;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): EventRecordInterface;

    public function getResourceLocale(): ?string;

    public function setResourceLocale(?string $resourceLocale): EventRecordInterface;

    public function getResourceWebspaceKey(): ?string;

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): EventRecordInterface;

    public function getResourceTitle(): ?string;

    public function setResourceTitle(?string $resourceTitle): EventRecordInterface;

    public function getResourceSecurityContext(): ?string;

    public function setResourceSecurityContext(?string $resourceSecurityContext): EventRecordInterface;

    public function getResourceSecurityType(): ?string;

    public function setResourceSecurityType(?string $resourceSecurityType): EventRecordInterface;
}
