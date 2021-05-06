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

interface ActivityInterface
{
    public function getEventType(): string;

    public function setEventType(string $eventType): ActivityInterface;

    /**
     * @return mixed[]
     */
    public function getEventContext(): array;

    /**
     * @param mixed[] $eventContext
     */
    public function setEventContext(array $eventContext): ActivityInterface;

    /**
     * @return mixed[]|null
     */
    public function getEventPayload(): ?array;

    /**
     * @param mixed[]|null $eventPayload
     */
    public function setEventPayload(?array $eventPayload): ActivityInterface;

    public function getEventDateTime(): \DateTimeImmutable;

    public function setEventDateTime(\DateTimeImmutable $eventDateTime): ActivityInterface;

    public function getEventBatch(): ?string;

    public function setEventBatch(?string $eventBatch): ActivityInterface;

    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): ActivityInterface;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): ActivityInterface;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): ActivityInterface;

    public function getResourceLocale(): ?string;

    public function setResourceLocale(?string $resourceLocale): ActivityInterface;

    public function getResourceWebspaceKey(): ?string;

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): ActivityInterface;

    public function getResourceTitle(): ?string;

    public function setResourceTitle(?string $resourceTitle): ActivityInterface;

    public function getResourceTitleLocale(): ?string;

    public function setResourceTitleLocale(?string $resourceTitleLocale): ActivityInterface;

    public function getResourceSecurityContext(): ?string;

    public function setResourceSecurityContext(?string $resourceSecurityContext): ActivityInterface;

    public function getResourceSecurityObjectType(): ?string;

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): ActivityInterface;

    public function getResourceSecurityObjectId(): ?string;

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): ActivityInterface;
}
