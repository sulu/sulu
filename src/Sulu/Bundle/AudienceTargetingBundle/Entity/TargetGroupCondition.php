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
    /**
     * @var int
     */
    private $id;

    private ?string $type = null;

    /**
     * @var mixed[]
     *
     * @Type("array")
     */
    private ?array $condition = null;

    private ?\Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface $rule = null;

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getCondition()
    {
        return $this->condition;
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;

        return $this;
    }

    public function getRule()
    {
        return $this->rule;
    }

    public function setRule(TargetGroupRuleInterface $rule)
    {
        $this->rule = $rule;

        return $this;
    }
}
