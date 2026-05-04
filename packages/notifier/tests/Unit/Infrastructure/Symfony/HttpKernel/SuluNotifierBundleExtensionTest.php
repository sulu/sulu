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
use Sulu\Notifier\Application\Factory\DomainEventNotificationFactory;
use Sulu\Notifier\Application\Factory\EventNotificationFactoryInterface;
use Sulu\Notifier\Application\Factory\FallbackNotificationFactory;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Sulu\Notifier\Application\Subscriber\EventNotificationSubscriber;
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

        self::assertTrue($container->hasDefinition('sulu_notifier.factory.fallback'));
        self::assertSame(FallbackNotificationFactory::class, $container->getDefinition('sulu_notifier.factory.fallback')->getClass());

        // Built-in factories carry the priority attribute
        $domainTag = $container->getDefinition('sulu_notifier.factory.domain_event')->getTag('sulu_notifier.notification_factory');
        self::assertSame([['priority' => -100]], $domainTag);

        $fallbackTag = $container->getDefinition('sulu_notifier.factory.fallback')->getTag('sulu_notifier.notification_factory');
        self::assertSame([['priority' => -1000]], $fallbackTag);

        // Auto-configuration is registered for the interface
        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(EventNotificationFactoryInterface::class, $autoconfigured);
        self::assertNotEmpty($autoconfigured[EventNotificationFactoryInterface::class]->getTag('sulu_notifier.notification_factory'));
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
        $loader = new PhpFileLoader($builder, new FileLocator());
        $instanceof = [];
        $configurator = new ContainerConfigurator($builder, $loader, $instanceof, '/dev/null', '/dev/null', 'test');

        $bundle = new SuluNotifierBundle();
        $bundle->loadExtension($config, $configurator, $builder);

        return $builder;
    }
}
