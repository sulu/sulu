<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle;

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
                'Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupInterface' => 'sulu.model.target_group.class',
                'Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupConditionInterface' => 'sulu.model.target_group_condition.class',
                'Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupRuleInterface' => 'sulu.model.target_group_rule.class',
                'Sulu\Bundle\AudienceTargetingBundle\Entity\TargetGroupWebspaceInterface' => 'sulu.model.target_group_webspace.class',
            ],
            $container
        );
    }
}
