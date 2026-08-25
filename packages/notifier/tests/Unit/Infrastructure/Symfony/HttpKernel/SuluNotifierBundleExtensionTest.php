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

namespace Sulu\Notifier\Tests\Unit\Infrastructure\Symfony\HttpKernel;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Application\Factory\DefaultNotificationFactory;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Sulu\Notifier\Application\Subscriber\EventNotificationSubscriber;
use Sulu\Notifier\Infrastructure\Sulu\Activity\DomainEventNotificationFactory;
use Sulu\Notifier\Infrastructure\Symfony\HttpKernel\SuluNotifierBundle;
use Sulu\Notifier\Tests\Application\Domain\Event\TestNonDomainEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class SuluNotifierBundleExtensionTest extends TestCase
{
    public function testLoadExtensionRegistersServicesAndParameter(): void
    {
        $container = $this->buildContainer([
            'channels' => [
                'chat/slack' => [DomainEvent::class, TestNonDomainEvent::class],
                'chat/discord' => [DomainEvent::class],
            ],
        ]);

        // Parameter exists and is the inverted map
        self::assertTrue($container->hasParameter('sulu_notifier.event_channel_map'));
        self::assertSame(
            [
                DomainEvent::class => ['chat/slack', 'chat/discord'],
                TestNonDomainEvent::class => ['chat/slack'],
            ],
            $container->getParameter('sulu_notifier.event_channel_map'),
        );

        // All services are registered
        self::assertTrue($container->hasDefinition('sulu_notifier.event_notifier'));
        self::assertSame(EventNotifier::class, $container->getDefinition('sulu_notifier.event_notifier')->getClass());

        self::assertTrue($container->hasDefinition('sulu_notifier.event_subscriber'));
        self::assertSame(EventNotificationSubscriber::class, $container->getDefinition('sulu_notifier.event_subscriber')->getClass());
        self::assertNotEmpty($container->getDefinition('sulu_notifier.event_subscriber')->getTag('kernel.event_subscriber'));

        self::assertTrue($container->hasDefinition('sulu_notifier.factory.domain_event'));
        self::assertSame(DomainEventNotificationFactory::class, $container->getDefinition('sulu_notifier.factory.domain_event')->getClass());

        self::assertTrue($container->hasDefinition('sulu_notifier.factory.default'));
        self::assertSame(DefaultNotificationFactory::class, $container->getDefinition('sulu_notifier.factory.default')->getClass());

        // Built-in factories carry the priority attribute
        $domainTag = $container->getDefinition('sulu_notifier.factory.domain_event')->getTag('sulu_notifier.notification_factory');
        self::assertSame([['priority' => -100]], $domainTag);

        $defaultTag = $container->getDefinition('sulu_notifier.factory.default')->getTag('sulu_notifier.notification_factory');
        self::assertSame([['priority' => -1000]], $defaultTag);

        // Auto-configuration is registered for the interface
        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(EventNotificationFactoryInterface::class, $autoconfigured);
        self::assertNotEmpty($autoconfigured[EventNotificationFactoryInterface::class]->getTag('sulu_notifier.notification_factory'));
    }

    public function testLoadExtensionTagsListenerForNonDomainEventClass(): void
    {
        $container = $this->buildContainer([
            'channels' => [
                'chat/slack' => [TestNonDomainEvent::class],
            ],
        ]);

        self::assertSame(
            [['event' => TestNonDomainEvent::class, 'method' => 'onEvent', 'priority' => -512]],
            $container->getDefinition('sulu_notifier.event_subscriber')->getTag('kernel.event_listener'),
        );
    }

    public function testLoadExtensionDoesNotTagDomainEventSubclassAsPlainListener(): void
    {
        $container = $this->buildContainer([
            'channels' => [
                'chat/slack' => [DomainEvent::class],
            ],
        ]);

        // DomainEvent is already covered by the kernel.event_subscriber tag,
        // it must not also get an explicit kernel.event_listener tag.
        self::assertSame(
            [],
            $container->getDefinition('sulu_notifier.event_subscriber')->getTag('kernel.event_listener'),
        );
    }

    public function testLoadExtensionWithEmptyConfig(): void
    {
        $container = $this->buildContainer([]);

        self::assertSame([], $container->getParameter('sulu_notifier.event_channel_map'));
        self::assertTrue($container->hasDefinition('sulu_notifier.event_subscriber'));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildContainer(array $config): ContainerBuilder
    {
        $builder = new ContainerBuilder(new ParameterBag([
            'kernel.default_locale' => 'en',
        ]));
        $builder->register('notifier', \Symfony\Component\Notifier\NotifierInterface::class);
        $loader = new PhpFileLoader($builder, new FileLocator());
        $instanceof = [];
        $configurator = new ContainerConfigurator($builder, $loader, $instanceof, '/dev/null', '/dev/null', 'test');

        $bundle = new SuluNotifierBundle();
        $bundle->loadExtension($config, $configurator, $builder);

        return $builder;
    }
}
