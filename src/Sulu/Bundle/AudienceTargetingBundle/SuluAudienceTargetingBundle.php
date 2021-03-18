<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle;

use Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler\AddRulesPass;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleRepositoryInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface;
use Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceRepositoryInterface;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Sulu Audience Targeting Bundle is for managing target groups, their rules and conditions
 * and applying them to certain contents to delivery user specific content.
 */
class SuluAudienceTargetingBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container)
    {
        $this->buildPersistence(
            [
                TargetGroupInterface::class => 'sulu.model.target_group.class',
                TargetGroupConditionInterface::class => 'sulu.model.target_group_condition.class',
                TargetGroupRuleInterface::class => 'sulu.model.target_group_rule.class',
                TargetGroupWebspaceInterface::class => 'sulu.model.target_group_webspace.class',
            ],
            $container
        );

        $container->addAliases(
            [
                TargetGroupRepositoryInterface::class => 'sulu.repository.target_group',
                TargetGroupConditionRepositoryInterface::class => 'sulu.repository.target_group_condition',
                TargetGroupRuleRepositoryInterface::class => 'sulu.repository.target_group_rule',
                TargetGroupWebspaceRepositoryInterface::class => 'sulu.repository.target_group_webspace',
            ]
        );

        $container->addCompilerPass(new AddRulesPass());
    }
}
