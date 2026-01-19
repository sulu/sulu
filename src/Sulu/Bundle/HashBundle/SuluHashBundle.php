<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HashBundle;

use Sulu\Component\Hash\AuditableHasher;
use Sulu\Component\Hash\RequestHashChecker;
use Sulu\Component\Hash\Serializer\Subscriber\HashSerializeEventSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SuluHashBundle extends AbstractBundle
{
    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set('sulu_hash.auditable_hasher', AuditableHasher::class);

        $services->set('sulu_hash.event_subscriber.serializer', HashSerializeEventSubscriber::class)
            ->args([
                new Reference('sulu_hash.auditable_hasher'),
            ])
            ->tag('jms_serializer.event_subscriber');

        $services->set('sulu_hash.request_hash_checker', RequestHashChecker::class)
            ->public()
            ->args([
                new Reference('sulu_hash.auditable_hasher'),
            ]);
    }
}
