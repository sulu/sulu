<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Entity class that holds rules for target groups.
 */
class TargetGroupRule implements TargetGroupRuleInterface
{
    private ?int $id = null;

    private string $title;

    private int $frequency;

    private TargetGroupInterface $targetGroup;

    /**
     * @var Collection<int, TargetGroupConditionInterface>
     */
    private Collection $conditions;

    /**
     * Initialize collections.
     */
    public function __construct()
    {
        $this->conditions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function setFrequency(int $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getTargetGroup(): TargetGroupInterface
    {
        return $this->targetGroup;
    }

    public function setTargetGroup(TargetGroupInterface $targetGroup): static
    {
        $this->targetGroup = $targetGroup;

        return $this;
    }

    public function getConditions(): Collection
    {
        return $this->conditions;
    }

    public function addCondition(TargetGroupConditionInterface $condition): static
    {
        $this->conditions[] = $condition;

        return $this;
    }

    public function removeCondition(TargetGroupConditionInterface $condition): static
    {
        $this->conditions->removeElement($condition);

        return $this;
    }

    public function clearConditions(): static
    {
        foreach ($this->conditions as $condition) {
            $this->removeCondition($condition);
        }

        return $this;
    }
}
