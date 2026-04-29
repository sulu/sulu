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

use Sulu\Component\Hash\AuditableHasher;
use Sulu\Component\Hash\RequestHashChecker;
use Sulu\Component\Hash\Serializer\Subscriber\HashSerializeEventSubscriber;
use Symfony\Component\DependencyInjection\Reference;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('sulu_hash.auditable_hasher', AuditableHasher::class);

    $services->set('sulu_hash.event_subscriber.serializer', HashSerializeEventSubscriber::class)
        ->args([new Reference('sulu_hash.auditable_hasher')])
        ->tag('jms_serializer.event_subscriber');

    $services->set('sulu_hash.request_hash_checker', RequestHashChecker::class)
        ->public()
        ->args([new Reference('sulu_hash.auditable_hasher')]);
};
