<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Domain\Model;

interface ReferenceInterface
{
    public const RESOURCE_KEY = 'references';
    public const LIST_KEY = 'references';

    public function getId(): int;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): static;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): static;

    public function getReferenceLocale(): string;

    public function setReferenceLocale(string $referenceLocale): static;

    /**
     * @return array<string, string>
     */
    public function getReferenceViewAttributes(): array;

    /**
     * @param array<string, string> $referenceViewAttributes
     */
    public function setReferenceViewAttributes(array $referenceViewAttributes): static;

    public function getReferenceResourceKey(): string;

    public function setReferenceResourceKey(string $referenceResourceKey): static;

    public function getReferenceResourceId(): string;

    public function setReferenceResourceId(string $referenceResourceId): static;

    public function getReferenceTitle(): string;

    public function setReferenceTitle(string $referenceTitle): static;

    public function getReferenceProperty(): string;

    public function setReferenceProperty(string $referenceProperty): static;

    public function getReferenceCount(): int;

    public function setReferenceCount(int $referenceCount): static;

    public function getReferenceLiveCount(): int;

    public function setReferenceLiveCount(int $referenceLiveCount): static;

    public function increaseReferenceCounter(): int;

    public function increaseReferenceLiveCounter(): int;

    /**
     * @param static $reference
     */
    public function equals(ReferenceInterface $reference): bool;
}
