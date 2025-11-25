<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Domain\Model;

use Sulu\Component\Security\Authentication\UserInterface;

interface TrashItemInterface
{
    public const RESOURCE_KEY = 'trash_items';
    public const LIST_KEY = 'trash_items';

    public function getId(): int;

    public function isNew(): bool;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): static;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): static;

    public function getRestoreType(): ?string;

    public function setRestoreType(?string $subResourceKey): static;

    /**
     * @return mixed[]
     */
    public function getRestoreData(): array;

    /**
     * @param mixed[] $restoreData
     */
    public function setRestoreData(array $restoreData): static;

    /**
     * @return mixed[]
     */
    public function getRestoreOptions(): array;

    /**
     * @param mixed[] $options
     */
    public function setRestoreOptions(array $options): static;

    public function getResourceTitle(?string $locale = null): string;

    public function setResourceTitle(string $resourceTitle, ?string $locale = null): static;

    public function getResourceSecurityContext(): ?string;

    public function setResourceSecurityContext(?string $resourceSecurityContext): static;

    public function getResourceSecurityObjectType(): ?string;

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): static;

    public function getResourceSecurityObjectId(): ?string;

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): static;

    public function getStoreTimestamp(): \DateTimeImmutable;

    public function setStoreTimestamp(\DateTimeImmutable $storeTimestamp): static;

    public function getUser(): ?UserInterface;

    public function getUserId(): ?int;

    public function setUser(?UserInterface $user): static;

    public function getTranslation(?string $locale = null, bool $fallback = false): TrashItemTranslation;
}
