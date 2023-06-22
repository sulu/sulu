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

class Reference implements ReferenceInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $resourceKey;

    /**
     * @var string
     */
    private $resourceId;

    /**
     * @var string
     */
    private $referenceResourceKey;

    /**
     * @var string
     */
    private $referenceResourceId;

    /**
     * @var string
     */
    private $referenceLocale;

    /**
     * @var array<string, string>
     */
    private $referenceViewAttributes = [];

    /**
     * @var string
     */
    private $referenceTitle;

    /**
     * @var int
     */
    private $referenceCount;

    /**
     * @var int
     */
    private $referenceLiveCount;

    /**
     * @var string
     */
    private $referenceProperty;

    public function getId(): int
    {
        return $this->id;
    }

    public function getResourceKey(): string
    {
        return $this->resourceKey;
    }

    public function setResourceKey(string $resourceKey): static
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): static
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getReferenceLocale(): string
    {
        return $this->referenceLocale;
    }

    public function setReferenceLocale(string $referenceLocale): static
    {
        $this->referenceLocale = $referenceLocale;

        return $this;
    }

    public function getReferenceViewAttributes(): array
    {
        return $this->referenceViewAttributes;
    }

    public function setReferenceViewAttributes(array $referenceViewAttributes): static
    {
        $this->referenceViewAttributes = $referenceViewAttributes;

        return $this;
    }

    public function getReferenceResourceKey(): string
    {
        return $this->referenceResourceKey;
    }

    public function setReferenceResourceKey(string $referenceResourceKey): static
    {
        $this->referenceResourceKey = $referenceResourceKey;

        return $this;
    }

    public function getReferenceResourceId(): string
    {
        return $this->referenceResourceId;
    }

    public function setReferenceResourceId(string $referenceResourceId): static
    {
        $this->referenceResourceId = $referenceResourceId;

        return $this;
    }

    public function getReferenceTitle(): string
    {
        return $this->referenceTitle;
    }

    public function setReferenceTitle(string $referenceTitle): static
    {
        $this->referenceTitle = $referenceTitle;

        return $this;
    }

    public function getReferenceProperty(): string
    {
        return $this->referenceProperty;
    }

    public function setReferenceProperty(string $referenceProperty): static
    {
        $this->referenceProperty = $referenceProperty;

        return $this;
    }

    public function getReferenceCount(): int
    {
        return $this->referenceCount;
    }

    public function setReferenceCount(int $referenceCount): static
    {
        $this->referenceCount = $referenceCount;

        return $this;
    }

    public function getReferenceLiveCount(): int
    {
        return $this->referenceLiveCount;
    }

    public function setReferenceLiveCount(int $referenceLiveCount): static
    {
        $this->referenceLiveCount = $referenceLiveCount;

        return $this;
    }

    public function increaseReferenceCounter(): int
    {
        return ++$this->referenceCount;
    }

    public function increaseReferenceLiveCounter(): int
    {
        return ++$this->referenceLiveCount;
    }

    public function equals(ReferenceInterface $reference): bool
    {
        return
            $this->resourceKey === $reference->resourceKey
            && $this->resourceId === $reference->resourceId
            && $this->referenceLocale === $reference->referenceLocale
            && $this->referenceResourceKey === $reference->referenceResourceKey
            && $this->referenceResourceId === $reference->referenceResourceId
            && $this->referenceProperty === $reference->referenceProperty
            && $this->referenceViewAttributes === $reference->referenceViewAttributes;
    }
}
