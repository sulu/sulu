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
    public const RESOURCE_KEY = 'activities';
    public const LIST_KEY = 'activities';

    public function getType(): string;

    public function setType(string $type): static;

    /**
     * @return mixed[]
     */
    public function getContext(): array;

    /**
     * @param mixed[] $context
     */
    public function setContext(array $context): static;

    /**
     * @return mixed[]|null
     */
    public function getPayload(): ?array;

    /**
     * @param mixed[]|null $payload
     */
    public function setPayload(?array $payload): static;

    public function getTimestamp(): \DateTimeImmutable;

    public function setTimestamp(\DateTimeImmutable $timestamp): static;

    public function getBatch(): ?string;

    public function setBatch(?string $batch): static;

    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): static;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): static;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): static;

    public function getResourceLocale(): ?string;

    public function setResourceLocale(?string $resourceLocale): static;

    public function getResourceWebspaceKey(): ?string;

    public function setResourceWebspaceKey(?string $resourceWebspaceKey): static;

    public function getResourceTitle(): ?string;

    public function setResourceTitle(?string $resourceTitle): static;

    public function getResourceTitleLocale(): ?string;

    public function setResourceTitleLocale(?string $resourceTitleLocale): static;

    public function getResourceSecurityContext(): ?string;

    public function setResourceSecurityContext(?string $resourceSecurityContext): static;

    public function getResourceSecurityObjectType(): ?string;

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): static;

    public function getResourceSecurityObjectId(): ?string;

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): static;
}
