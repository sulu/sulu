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

use Sulu\Bundle\TestBundle\Testing\TestUserProvider;
use Sulu\Bundle\TestBundle\Testing\TestVoter;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('test_user_provider', TestUserProvider::class)
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.repository.user'),
            new Reference('security.password_hasher_factory'),
            new Reference('sulu_security.user_provider'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('test_voter', TestVoter::class)
        ->private()
        ->tag('security.voter');
};
