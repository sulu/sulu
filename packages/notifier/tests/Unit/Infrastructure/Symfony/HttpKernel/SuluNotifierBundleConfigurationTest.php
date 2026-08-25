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
use Sulu\Notifier\Infrastructure\Symfony\HttpKernel\SuluNotifierBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Notifier\NotifierInterface;

/**
 * Exercises the real bundle configuration/loading path (Bundle::getContainerExtension()
 * -> BundleExtension::load()), rather than a parallel ConfigurationInterface -- there is
 * no separate Configuration class to keep in sync with SuluNotifierBundle::configure().
 */
class SuluNotifierBundleConfigurationTest extends TestCase
{
    public function testEmptyConfigIsValid(): void
    {
        $container = $this->loadConfig([]);

        self::assertSame([], $container->getParameter('sulu_notifier.event_channel_map'));
    }

    public function testValidChannelMappingProcesses(): void
    {
        $container = $this->loadConfig([
            'channels' => [
                'chat/slack' => [DomainEvent::class],
            ],
        ]);

        self::assertSame(
            [DomainEvent::class => ['chat/slack']],
            $container->getParameter('sulu_notifier.event_channel_map'),
        );
    }

    public function testNonExistentClassIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('does not exist or is not autoloadable');

        $this->loadConfig([
            'channels' => [
                'chat/slack' => ['Sulu\Does\Not\Exist'],
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadConfig(array $config): ContainerBuilder
    {
        $builder = new ContainerBuilder(new ParameterBag([
            'kernel.default_locale' => 'en',
            'kernel.environment' => 'test',
            'kernel.build_dir' => \sys_get_temp_dir() . '/sulu-notifier-bundle-configuration-test',
        ]));
        $builder->register('notifier', NotifierInterface::class);

        $bundle = new SuluNotifierBundle();
        $extension = $bundle->getContainerExtension();

        if (!$extension instanceof ExtensionInterface) {
            throw new \LogicException('Expected SuluNotifierBundle to expose a container extension.');
        }

        $extension->load([$config], $builder);

        return $builder;
    }
}
