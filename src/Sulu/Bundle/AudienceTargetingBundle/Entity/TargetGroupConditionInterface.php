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

/**
 * Interface for target group conditions.
 */
interface TargetGroupConditionInterface
{
    public function getId(): int;

    public function getType(): string;

    public function setType(string $type): static;

    /**
     * @return mixed[]
     */
    public function getCondition(): array;

    /**
     * @param mixed[] $condition
     */
    public function setCondition(array $condition): static;

    public function getRule(): TargetGroupRuleInterface;

    public function setRule(TargetGroupRuleInterface $rule): static;
}
