<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Functional\Infrastructure\Symfony\HttpKernel;

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Infrastructure\Symfony\HttpKernel\SuluRouteBundle;

#[CoversClass(SuluRouteBundle::class)]
class SuluRouteBundleTest extends KernelTestCase
{
    public function testServices(): void
    {
        $expectedServiceIdsToExists = [
            'sulu_route.doctrine_route_changed_updater',
            'sulu_route.route_repository',
            'sulu_route.symfony_cmf_route_provider',
            'sulu_route.route_generator',
            // 'sulu_route.route_loader',
            // 'sulu_route.route_history_defaults_provider',
        ];

        $container = self::getContainer();

        foreach ($expectedServiceIdsToExists as $serviceId) {
            $this->assertTrue($container->has($serviceId), 'Service "' . $serviceId . '" not found.');
        }
    }

    public function testAliases(): void
    {
        $expectedAliases = [
            RouteRepositoryInterface::class => 'sulu_route.route_repository',
            RouteGeneratorInterface::class => 'sulu_route.route_generator',
        ];

        $container = self::getContainer();

        foreach ($expectedAliases as $alias => $serviceId) {
            $this->assertTrue($container->has($alias), 'Alias "' . $serviceId . '" not found.');
            $this->assertTrue($container->has($serviceId), 'Service "' . $serviceId . '" not found.');
        }
    }
}
