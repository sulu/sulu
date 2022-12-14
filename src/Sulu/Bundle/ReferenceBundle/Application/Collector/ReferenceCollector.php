<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Application\Collector;

use Doctrine\Common\Collections\ArrayCollection;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Component\Content\Document\WorkflowStage;

/**
 * @internal
 */
class ReferenceCollector
{
    /**
     * @var ArrayCollection<int, ReferenceInterface>
     */
    private $referenceCollection;

    /**
     * @var ReferenceRepositoryInterface
     */
    private $referenceRepository;

    /**
     * @var string
     */
    private $referenceResourceId;

    /**
     * @var string
     */
    private $referenceResourceKey;

    /**
     * @var string
     */
    private $referenceTitle;

    /**
     * @var string
     */
    private $referenceLocale;

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
    private $referenceWorkflowStage;

    public function __construct(
        ReferenceRepositoryInterface $referenceRepository,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        ?string $referenceSecurityContext = null,
        ?string $referenceSecurityObjectId = null,
        ?string $referenceSecurityObjectType = null,
        ?int $referenceWorkflowStage = null
    ) {
        $this->referenceRepository = $referenceRepository;
        $this->referenceCollection = new ArrayCollection();

        $this->referenceResourceKey = $referenceResourceKey;
        $this->referenceResourceId = $referenceResourceId;
        $this->referenceLocale = $referenceLocale;
        $this->referenceTitle = $referenceTitle;
        $this->referenceSecurityContext = $referenceSecurityContext;
        $this->referenceSecurityObjectType = $referenceSecurityObjectType;
        $this->referenceSecurityObjectId = $referenceSecurityObjectId;
        $this->referenceWorkflowStage = $referenceWorkflowStage ?? WorkflowStage::TEST;
    }

    public function addReference(
        string $resourceKey,
        string $resourceId,
        string $title,
        string $property,
        ?string $securityContext = null,
        ?string $securityObjectType = null,
        ?string $securityObjectId = null
    ): ReferenceInterface {
        $reference = $this->referenceRepository->create(
            $resourceKey,
            $resourceId,
            $title,
            $this->referenceLocale,
            $property,
            $this->referenceResourceKey,
            $this->referenceResourceId,
            $this->referenceTitle,
            $securityContext,
            $securityObjectType,
            $securityObjectId,
            $this->referenceSecurityContext,
            $this->referenceSecurityObjectType,
            $this->referenceSecurityObjectId
        );

        $existingReference = $this->getReference($reference);

        $reference = $existingReference ?? $reference;
        if ($existingReference) {
            $reference->increaseReferenceCounter();
            if (WorkflowStage::PUBLISHED === $this->referenceWorkflowStage) {
                $reference->increaseReferenceLiveCounter();
            }
        }

        $this->referenceCollection->add($reference);
        $this->referenceRepository->add($reference);

        return $reference;
    }

    public function persistReferences(): void
    {
        $this->referenceRepository->removeByReferenceResourceKeyAndId(
            $this->referenceResourceKey,
            $this->referenceResourceId,
            $this->referenceLocale
        );

        $this->referenceRepository->flush();
    }

    public function getReference(ReferenceInterface $reference): ?ReferenceInterface
    {
        return $this->referenceCollection->filter(fn (ReferenceInterface $ref) => $ref->equals($reference))->first() ?: null;
    }

    public function getReferenceRepository(): ReferenceRepositoryInterface
    {
        return $this->referenceRepository;
    }

    public function setReferenceRepository(ReferenceRepositoryInterface $referenceRepository): ReferenceCollector
    {
        $this->referenceRepository = $referenceRepository;

        return $this;
    }

    public function getReferenceResourceId(): string
    {
        return $this->referenceResourceId;
    }

    public function setReferenceResourceId(string $referenceResourceId): ReferenceCollector
    {
        $this->referenceResourceId = $referenceResourceId;

        return $this;
    }

    public function getReferenceResourceKey(): string
    {
        return $this->referenceResourceKey;
    }

    public function setReferenceResourceKey(string $referenceResourceKey): ReferenceCollector
    {
        $this->referenceResourceKey = $referenceResourceKey;

        return $this;
    }

    public function getReferenceTitle(): string
    {
        return $this->referenceTitle;
    }

    public function setReferenceTitle(string $referenceTitle): ReferenceCollector
    {
        $this->referenceTitle = $referenceTitle;

        return $this;
    }

    public function getReferenceLocale(): string
    {
        return $this->referenceLocale;
    }

    public function setReferenceLocale(string $referenceLocale): ReferenceCollector
    {
        $this->referenceLocale = $referenceLocale;

        return $this;
    }

    public function getReferenceSecurityContext(): ?string
    {
        return $this->referenceSecurityContext;
    }

    public function setReferenceSecurityContext(?string $referenceSecurityContext): ReferenceCollector
    {
        $this->referenceSecurityContext = $referenceSecurityContext;

        return $this;
    }

    public function getReferenceSecurityObjectType(): ?string
    {
        return $this->referenceSecurityObjectType;
    }

    public function setReferenceSecurityObjectType(?string $referenceSecurityObjectType): ReferenceCollector
    {
        $this->referenceSecurityObjectType = $referenceSecurityObjectType;

        return $this;
    }

    public function getReferenceSecurityObjectId(): ?string
    {
        return $this->referenceSecurityObjectId;
    }

    public function setReferenceSecurityObjectId(?string $referenceSecurityObjectId): ReferenceCollector
    {
        $this->referenceSecurityObjectId = $referenceSecurityObjectId;

        return $this;
    }

    public function getReferenceWorkflowStage(): int
    {
        return $this->referenceWorkflowStage;
    }

    public function setReferenceWorkflowStage(int $referenceWorkflowStage): ReferenceCollector
    {
        $this->referenceWorkflowStage = $referenceWorkflowStage;

        return $this;
    }
}
