<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Entity;

use JMS\Serializer\Annotation\Type;

/**
 * Entity that holds conditions for target group rules.
 */
class TargetGroupCondition implements TargetGroupConditionInterface
{
    private int $id;

    private string $type;

    /**
     * @var mixed[]
     */
    #[Type('array')]
    private array $condition;

    private TargetGroupRuleInterface $rule;

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCondition(): array
    {
        return $this->condition;
    }

    public function setCondition(array $condition): static
    {
        $this->condition = $condition;

        return $this;
    }

    public function getRule(): TargetGroupRuleInterface
    {
        return $this->rule;
    }

    public function setRule(TargetGroupRuleInterface $rule): static
    {
        $this->rule = $rule;

        return $this;
    }
}
