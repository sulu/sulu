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

class ReferenceCollector implements ReferenceCollectorInterface
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
     * @var array<string, string>
     */
    private $referenceViewAttributes;

    /**
     * @var string
     */
    private $referenceTitle;

    /**
     * @var string
     */
    private $referenceLocale;

    /**
     * @var int
     */
    private $referenceWorkflowStage;

    /**
     * @param array<string, string> $referenceViewAttributes
     */
    public function __construct(
        ReferenceRepositoryInterface $referenceRepository,
        string $referenceResourceKey,
        string $referenceResourceId,
        string $referenceLocale,
        string $referenceTitle,
        array $referenceViewAttributes = [],
        ?int $referenceWorkflowStage = null
    ) {
        $this->referenceRepository = $referenceRepository;
        $this->referenceCollection = new ArrayCollection();

        $this->referenceResourceKey = $referenceResourceKey;
        $this->referenceResourceId = $referenceResourceId;
        $this->referenceLocale = $referenceLocale;
        $this->referenceTitle = $referenceTitle;
        $this->referenceViewAttributes = $referenceViewAttributes;
        $this->referenceWorkflowStage = $referenceWorkflowStage ?? WorkflowStage::TEST;
    }

    public function addReference(
        string $resourceKey,
        string $resourceId,
        string $referenceProperty,
    ): ReferenceInterface {
        $reference = $this->referenceRepository->create(
            $resourceKey,
            $resourceId,
            $this->referenceResourceKey,
            $this->referenceResourceId,
            $this->referenceLocale,
            $this->referenceTitle,
            $referenceProperty,
            $this->referenceViewAttributes
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

        return $reference;
    }

    public function persistReferences(): void
    {
        $this->referenceRepository->removeBy([
            'referenceResourceKey' => $this->referenceResourceKey,
            'referenceResourceId' => $this->referenceResourceId,
            'referenceLocale' => $this->referenceLocale,
        ]);

        foreach ($this->referenceCollection as $reference) {
            $this->referenceRepository->add($reference);
        }

        $this->referenceCollection->clear();
    }

    private function getReference(ReferenceInterface $reference): ?ReferenceInterface
    {
        return $this->referenceCollection->filter(fn (ReferenceInterface $ref) => $ref->equals($reference))->first() ?: null;
    }
}
