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

use Doctrine\Common\Collections\Collection;

/**
 * Interface for target group rule entity.
 */
interface TargetGroupRuleInterface
{
    public const FREQUENCY_HIT = 1;

    public const FREQUENCY_SESSION = 2;

    public const FREQUENCY_VISITOR = 3;

    public function getId(): int;

    public function getTitle(): string;

    public function setTitle(string $title): static;

    public function getFrequency(): int;

    public function setFrequency(int $frequency): static;

    public function getTargetGroup(): TargetGroupInterface;

    public function setTargetGroup(TargetGroupInterface $targetGroup): static;

    /**
     * @return Collection<int, TargetGroupConditionInterface>
     */
    public function getConditions(): Collection;

    public function addCondition(TargetGroupConditionInterface $condition): static;

    public function removeCondition(TargetGroupConditionInterface $condition): static;

    public function clearConditions(): static;
}
