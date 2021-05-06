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
    public function getType(): string;

    public function setType(string $eventType): ActivityInterface;

    /**
     * @return mixed[]
     */
    public function getContext(): array;

    /**
     * @param mixed[] $eventContext
     */
    public function setContext(array $eventContext): ActivityInterface;

    /**
     * @return mixed[]|null
     */
    public function getPayload(): ?array;

    /**
     * @param mixed[]|null $eventPayload
     */
    public function setPayload(?array $eventPayload): ActivityInterface;

    public function getTimestamp(): \DateTimeImmutable;

    public function setTimestamp(\DateTimeImmutable $eventDateTime): ActivityInterface;

    public function getBatch(): ?string;

    public function setBatch(?string $eventBatch): ActivityInterface;

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
