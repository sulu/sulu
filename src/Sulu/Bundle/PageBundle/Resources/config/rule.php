<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Sulu\Bundle\PageBundle\Rule\PageRule;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_audience_targeting.rules.page', PageRule::class)
        ->args([
            new Reference('request_stack'),
            new Reference('sulu_core.webspace.request_analyzer'),
            new Reference('translator'),
            new Reference('sulu.content.resource_locator.strategy_pool'),
            '%sulu_audience_targeting.hit.headers.uuid%',
            '%sulu_audience_targeting.headers.url%',
        ])
        ->tag('sulu.audience_target_rule', ['alias' => 'page']);
};
