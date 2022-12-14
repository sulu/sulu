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
     * @var ?int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

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
     * @var string|null
     */
    private $securityContext;

    /**
     * @var string|null
     */
    private $securityObjectType;

    /**
     * @var string|null
     */
    private $securityObjectId;

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
    private $property;

    public function getId(): ?int
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): ReferenceInterface
    {
        $this->title = $title;

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

    public function getSecurityContext(): ?string
    {
        return $this->securityContext;
    }

    public function setSecurityContext(?string $securityContext): ReferenceInterface
    {
        $this->securityContext = $securityContext;

        return $this;
    }

    public function getSecurityObjectType(): ?string
    {
        return $this->securityObjectType;
    }

    public function setSecurityObjectType(?string $securityObjectType): ReferenceInterface
    {
        $this->securityObjectType = $securityObjectType;

        return $this;
    }

    public function getSecurityObjectId(): ?string
    {
        return $this->securityObjectId;
    }

    public function setSecurityObjectId(?string $securityObjectId): ReferenceInterface
    {
        $this->securityObjectId = $securityObjectId;

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

    public function getProperty(): string
    {
        return $this->property;
    }

    public function setProperty(string $property): ReferenceInterface
    {
        $this->property = $property;

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
            $this->resourceKey === $reference->getResourceKey() &&
            $this->resourceId === $reference->getResourceId() &&
            $this->locale === $reference->getLocale() &&
            $this->securityContext === $reference->getSecurityContext() &&
            $this->securityObjectType === $reference->getSecurityObjectType() &&
            $this->securityObjectId === $reference->getSecurityObjectId() &&
            $this->referenceResourceKey === $reference->getReferenceResourceKey() &&
            $this->referenceResourceId === $reference->getReferenceResourceId() &&
            $this->referenceSecurityContext === $reference->getReferenceSecurityContext() &&
            $this->referenceSecurityObjectType === $reference->getReferenceSecurityObjectType() &&
            $this->referenceSecurityObjectId === $reference->getReferenceSecurityObjectId() &&
            $this->property === $reference->getProperty();
    }
}
