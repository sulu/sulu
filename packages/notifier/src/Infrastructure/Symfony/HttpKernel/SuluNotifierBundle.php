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

namespace Sulu\Notifier\Infrastructure\Symfony\HttpKernel;

use Sulu\Notifier\Application\Factory\DomainEventNotificationFactory;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Sulu\Notifier\Application\Factory\FallbackNotificationFactory;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Sulu\Notifier\Application\Subscriber\EventNotificationSubscriber;
use Sulu\Notifier\Infrastructure\Symfony\DependencyInjection\Compiler\RegisterEventListenersPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @codeCoverageIgnore
 */
final class SuluNotifierBundle extends AbstractBundle
{
    public function __construct()
    {
        $this->name = 'SuluNotifierBundle';
        $this->extensionAlias = 'sulu_notifier';
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode() // @phpstan-ignore-line
            ->children()
                ->arrayNode('channels')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->scalarPrototype()
                            ->validate()
                                ->ifTrue(static fn ($value): bool => !\is_string($value) || !\class_exists($value))
                                ->thenInvalid('Event class %s does not exist or is not autoloadable.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        /** @var array<string, list<class-string>> $channels */
        $channels = $config['channels'] ?? [];

        $eventChannelMap = [];
        foreach ($channels as $channelName => $eventClasses) {
            foreach ($eventClasses as $eventClass) {
                $eventChannelMap[$eventClass] ??= [];
                if (!\in_array($channelName, $eventChannelMap[$eventClass], true)) {
                    $eventChannelMap[$eventClass][] = $channelName;
                }
            }
        }

        $builder->setParameter('sulu_notifier.event_channel_map', $eventChannelMap);

        $builder->registerForAutoconfiguration(EventNotificationFactoryInterface::class)
            ->addTag('sulu_notifier.notification_factory');

        $services = $container->services();

        $services->set('sulu_notifier.factory.domain_event', DomainEventNotificationFactory::class)
            ->args([service('translator'), param('kernel.default_locale')])
            ->tag('sulu_notifier.notification_factory', ['priority' => -100]);

        $services->set('sulu_notifier.factory.fallback', FallbackNotificationFactory::class)
            ->args([service('translator'), param('kernel.default_locale')])
            ->tag('sulu_notifier.notification_factory', ['priority' => -1000]);

        $services->set('sulu_notifier.event_notifier', EventNotifier::class)
            ->args([
                tagged_iterator('sulu_notifier.notification_factory'),
                service('notifier'),
                param('sulu_notifier.event_channel_map'),
                service('logger'),
            ]);

        $services->set('sulu_notifier.event_subscriber', EventNotificationSubscriber::class)
            ->args([service('sulu_notifier.event_notifier')])
            ->tag('kernel.event_subscriber');
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterEventListenersPass());
    }

    /**
     * @internal this method is not part of the public API and should only be called by the Symfony framework classes
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 4);
    }
}
