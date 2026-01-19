<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\TestBundle\Entity\TestUserRepository;
use Sulu\Bundle\TestBundle\Testing\TestUserProvider;
use Sulu\Bundle\TestBundle\Testing\TestVoter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $parameters = $container->parameters();
    $parameters->set('sulu.test_user_provider.class', TestUserProvider::class);
    $parameters->set('sulu.test_voter.class', TestVoter::class);
    $parameters->set('sulu_test.test_user_repository.class', TestUserRepository::class);

    $services = $container->services();

    $services->set('test_user_provider', '%sulu.test_user_provider.class%')
        ->public()
        ->args([
            new Reference('doctrine.orm.entity_manager'),
            new Reference('sulu.repository.contact'),
            new Reference('sulu.repository.user'),
            new Reference('sulu_security.encoder_factory'),
            new Reference('sulu_security.user_provider'),
        ])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('test_voter', '%sulu.test_voter.class%')
        ->tag('security.voter');
};
