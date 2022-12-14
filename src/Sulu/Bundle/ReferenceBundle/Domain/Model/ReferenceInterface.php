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

    public function getId(): ?int;

    public function getTitle(): string;

    public function setTitle(string $title): ReferenceInterface;

    public function getResourceKey(): string;

    public function setResourceKey(string $resourceKey): ReferenceInterface;

    public function getResourceId(): string;

    public function setResourceId(string $resourceId): ReferenceInterface;

    public function getLocale(): string;

    public function setLocale(string $locale): ReferenceInterface;

    public function getSecurityContext(): ?string;

    public function setSecurityContext(?string $securityContext): ReferenceInterface;

    public function getSecurityObjectType(): ?string;

    public function setSecurityObjectType(?string $securityObjectType): ReferenceInterface;

    public function getSecurityObjectId(): ?string;

    public function setSecurityObjectId(?string $securityObjectId): ReferenceInterface;

    public function getReferenceResourceKey(): string;

    public function setReferenceResourceKey(string $referenceResourceKey): ReferenceInterface;

    public function getReferenceResourceId(): string;

    public function setReferenceResourceId(string $referenceResourceId): ReferenceInterface;

    public function getReferenceTitle(): string;

    public function setReferenceTitle(string $referenceTitle): ReferenceInterface;

    public function getReferenceSecurityContext(): ?string;

    public function setReferenceSecurityContext(?string $referenceSecurityContext): ReferenceInterface;

    public function getReferenceSecurityObjectType(): ?string;

    public function setReferenceSecurityObjectType(?string $referenceSecurityObjectType): ReferenceInterface;

    public function getReferenceSecurityObjectId(): ?string;

    public function setReferenceSecurityObjectId(?string $referenceSecurityObjectId): ReferenceInterface;

    public function getProperty(): string;

    public function setProperty(string $property): ReferenceInterface;

    public function getReferenceCount(): int;

    public function setReferenceCount(int $referenceCount): ReferenceInterface;

    public function getReferenceLiveCount(): int;

    public function setReferenceLiveCount(int $referenceLiveCount): ReferenceInterface;

    public function increaseReferenceCounter(): int;

    public function increaseReferenceLiveCounter(): int;

    public function equals(ReferenceInterface $reference): bool;
}
