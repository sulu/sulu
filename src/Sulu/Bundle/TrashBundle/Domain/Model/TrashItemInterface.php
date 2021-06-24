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
    const RESOURCE_KEY = 'trash_items';
    const LIST_KEY = 'trash_items';

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): self;

    /**
     * @return mixed[]
     */
    public function getRestoreData(): array;

    /**
     * @param mixed[] $restoreData
     */
    public function setRestoreData(array $restoreData): self;

    public function getResourceTitle(): string;

    public function setResourceTitle(string $resourceTitle): self;

    public function getResourceSecurityContext(): ?string;

    public function setResourceSecurityContext(?string $resourceSecurityContext): self;

    public function getResourceSecurityObjectType(): ?string;

    public function setResourceSecurityObjectType(?string $resourceSecurityObjectType): self;

    public function getResourceSecurityObjectId(): ?string;

    public function setResourceSecurityObjectId(?string $resourceSecurityObjectId): self;

    public function getTimestamp(): \DateTimeImmutable;

    public function setTimestamp(\DateTimeImmutable $timestamp): self;

    public function getUser(): ?UserInterface;

    public function setUser(?UserInterface $user): self;
}
