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
    private $locale;

    /**
     * @var string
     */
    private $referenceResourceKey;

    /**
     * @var string
     */
    private $referenceResourceId;

    /**
     * @var array<string, string>
     */
    private $referenceViewAttributes = [];

    /**
     * @var string
     */
    private $referenceTitle;

    /**
     * @var string|null
     */
    private $referenceSecurityContext;

    /**
     * @var string|null
     */
    private $referenceSecurityObjectType;

    /**
     * @var string|null
     */
    private $referenceSecurityObjectId;

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

    public function setResourceKey(string $resourceKey): ReferenceInterface
    {
        $this->resourceKey = $resourceKey;

        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): ReferenceInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function getReferenceViewAttributes(): array
    {
        return $this->referenceViewAttributes;
    }

    public function setReferenceViewAttributes(array $referenceViewAttributes): ReferenceInterface
    {
        $this->referenceViewAttributes = $referenceViewAttributes;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): ReferenceInterface
    {
        $this->locale = $locale;

        return $this;
    }

    public function getReferenceResourceKey(): string
    {
        return $this->referenceResourceKey;
    }

    public function setReferenceResourceKey(string $referenceResourceKey): ReferenceInterface
    {
        $this->referenceResourceKey = $referenceResourceKey;

        return $this;
    }

    public function getReferenceResourceId(): string
    {
        return $this->referenceResourceId;
    }

    public function setReferenceResourceId(string $referenceResourceId): ReferenceInterface
    {
        $this->referenceResourceId = $referenceResourceId;

        return $this;
    }

    public function getReferenceTitle(): string
    {
        return $this->referenceTitle;
    }

    public function setReferenceTitle(string $referenceTitle): ReferenceInterface
    {
        $this->referenceTitle = $referenceTitle;

        return $this;
    }

    public function getReferenceSecurityContext(): ?string
    {
        return $this->referenceSecurityContext;
    }

    public function setReferenceSecurityContext(?string $referenceSecurityContext): ReferenceInterface
    {
        $this->referenceSecurityContext = $referenceSecurityContext;

        return $this;
    }

    public function getReferenceSecurityObjectType(): ?string
    {
        return $this->referenceSecurityObjectType;
    }

    public function setReferenceSecurityObjectType(?string $referenceSecurityObjectType): ReferenceInterface
    {
        $this->referenceSecurityObjectType = $referenceSecurityObjectType;

        return $this;
    }

    public function getReferenceSecurityObjectId(): ?string
    {
        return $this->referenceSecurityObjectId;
    }

    public function setReferenceSecurityObjectId(?string $referenceSecurityObjectId): ReferenceInterface
    {
        $this->referenceSecurityObjectId = $referenceSecurityObjectId;

        return $this;
    }

    public function getReferenceProperty(): string
    {
        return $this->referenceProperty;
    }

    public function setReferenceProperty(string $referenceProperty): ReferenceInterface
    {
        $this->referenceProperty = $referenceProperty;

        return $this;
    }

    public function getReferenceCount(): int
    {
        return $this->referenceCount;
    }

    public function setReferenceCount(int $referenceCount): ReferenceInterface
    {
        $this->referenceCount = $referenceCount;

        return $this;
    }

    public function getReferenceLiveCount(): int
    {
        return $this->referenceLiveCount;
    }

    public function setReferenceLiveCount(int $referenceLiveCount): ReferenceInterface
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
            $this->resourceKey === $reference->getResourceKey()
            && $this->resourceId === $reference->getResourceId()
            && $this->locale === $reference->getLocale()
            && $this->referenceResourceKey === $reference->getReferenceResourceKey()
            && $this->referenceResourceId === $reference->getReferenceResourceId()
            && $this->referenceSecurityContext === $reference->getReferenceSecurityContext()
            && $this->referenceSecurityObjectType === $reference->getReferenceSecurityObjectType()
            && $this->referenceSecurityObjectId === $reference->getReferenceSecurityObjectId()
            && $this->referenceProperty === $reference->getReferenceProperty();
    }
}
