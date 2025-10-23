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

namespace Sulu\CustomUrl\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\CustomUrl\Domain\Model\CustomUrl;
use Sulu\CustomUrl\Domain\Model\CustomUrlRoute;

class CustomUrlTest extends TestCase
{
    public function testGetCurrentRouteReturnsActiveRoute(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');
        $customUrl->setDomainParts(['test']);
        $customUrl->generateRoutes();

        $currentRoute = $customUrl->getCurrentRoute();

        $this->assertNotNull($currentRoute);
        $this->assertSame('example.com/test', $currentRoute->getPath());
        $this->assertFalse($currentRoute->isHistory());
    }

    public function testGetCurrentRouteReturnsNullWhenNoRoutes(): void
    {
        $customUrl = new CustomUrl();

        $currentRoute = $customUrl->getCurrentRoute();

        $this->assertNull($currentRoute);
    }

    public function testGetCurrentRouteIgnoresHistoryRoutes(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');
        $customUrl->setDomainParts(['old']);
        $customUrl->generateRoutes();

        // Change path to create history
        $customUrl->setDomainParts(['new']);
        $customUrl->generateRoutes();

        $currentRoute = $customUrl->getCurrentRoute();

        $this->assertNotNull($currentRoute);
        $this->assertSame('example.com/new', $currentRoute->getPath());
        $this->assertFalse($currentRoute->isHistory(), 'Current route should not be history');
    }

    public function testRouteHistoryCreatedOnPathChange(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');
        $customUrl->setDomainParts(['original']);
        $customUrl->generateRoutes();

        // Change path
        $customUrl->setDomainParts(['modified']);
        $customUrl->generateRoutes();

        $routes = \iterator_to_array($customUrl->getRoutes());
        $this->assertCount(2, $routes, 'Should have 2 routes after path change');

        // First route should be marked as history
        $oldRoute = $routes[0];
        $this->assertSame('example.com/original', $oldRoute->getPath());
        $this->assertTrue($oldRoute->isHistory(), 'Old route should be marked as history');

        // Second route should be active
        $newRoute = $routes[1];
        $this->assertSame('example.com/modified', $newRoute->getPath());
        $this->assertFalse($newRoute->isHistory(), 'New route should be active');

        // Old route should link to new route
        $this->assertSame(
            $newRoute->getUuid(),
            $oldRoute->getTargetRoute()?->getUuid(),
            'Old route should link to new route',
        );
    }

    public function testMultiplePathChangesCreateHistoryChain(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');

        // Version 1
        $customUrl->setDomainParts(['v1']);
        $customUrl->generateRoutes();

        // Version 2
        $customUrl->setDomainParts(['v2']);
        $customUrl->generateRoutes();

        // Version 3
        $customUrl->setDomainParts(['v3']);
        $customUrl->generateRoutes();

        $routes = \iterator_to_array($customUrl->getRoutes());
        $this->assertCount(3, $routes, 'Should have 3 routes after 2 changes');

        // Check v1 (oldest history)
        $v1Route = $routes[0];
        $this->assertSame('example.com/v1', $v1Route->getPath());
        $this->assertTrue($v1Route->isHistory());

        // Check v2 (middle history)
        $v2Route = $routes[1];
        $this->assertSame('example.com/v2', $v2Route->getPath());
        $this->assertTrue($v2Route->isHistory());

        // Check v3 (current active)
        $v3Route = $routes[2];
        $this->assertSame('example.com/v3', $v3Route->getPath());
        $this->assertFalse($v3Route->isHistory());

        // Verify history chain
        $this->assertSame($v2Route->getUuid(), $v1Route->getTargetRoute()?->getUuid(), 'v1 should link to v2');
        $this->assertSame($v3Route->getUuid(), $v2Route->getTargetRoute()?->getUuid(), 'v2 should link to v3');
    }

    public function testNoHistoryCreatedWhenPathUnchanged(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');
        $customUrl->setDomainParts(['test']);
        $customUrl->generateRoutes();

        // Generate routes again with same path
        $customUrl->generateRoutes();

        $routes = \iterator_to_array($customUrl->getRoutes());
        $this->assertCount(1, $routes, 'Should still have only 1 route when path unchanged');
        $this->assertFalse($routes[0]->isHistory(), 'Route should still be active');
    }

    public function testComplexDomainPartsWithHistory(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('*.example.com/*');

        // First path: subdomain1.example.com/path1
        $customUrl->setDomainParts(['subdomain1', 'path1']);
        $customUrl->generateRoutes();

        // Second path: subdomain2.example.com/path2
        $customUrl->setDomainParts(['subdomain2', 'path2']);
        $customUrl->generateRoutes();

        $routes = \iterator_to_array($customUrl->getRoutes());
        $this->assertCount(2, $routes);

        // Old route
        $this->assertSame('subdomain1.example.com/path1', $routes[0]->getPath());
        $this->assertTrue($routes[0]->isHistory());

        // New route
        $this->assertSame('subdomain2.example.com/path2', $routes[1]->getPath());
        $this->assertFalse($routes[1]->isHistory());
    }

    public function testMarkRouteAsHistoryWithExplicitNewRoute(): void
    {
        $customUrl = new CustomUrl();

        $oldRoute = new CustomUrlRoute($customUrl, 'example.com/old');
        $newRoute = new CustomUrlRoute($customUrl, 'example.com/new');

        $customUrl->addRoute($oldRoute);
        $customUrl->addRoute($newRoute);

        // Manually mark old route as history
        $customUrl->markRouteAsHistory($oldRoute, $newRoute);

        $this->assertTrue($oldRoute->isHistory());
        $this->assertSame($newRoute->getUuid(), $oldRoute->getTargetRoute()?->getUuid());
    }

    public function testMarkRouteAsHistoryWithImplicitNewRoute(): void
    {
        $customUrl = new CustomUrl();
        $customUrl->setBaseDomain('example.com/*');
        $customUrl->setDomainParts(['new']);
        $customUrl->generateRoutes();

        $oldRoute = new CustomUrlRoute($customUrl, 'example.com/old');
        $customUrl->addRoute($oldRoute);

        // Mark as history without specifying new route (should use current route)
        $customUrl->markRouteAsHistory($oldRoute);

        $this->assertTrue($oldRoute->isHistory());
        $this->assertNotNull($oldRoute->getTargetRoute());
        $this->assertSame('example.com/new', $oldRoute->getTargetRoute()->getPath());
    }

    public function testMarkRouteAsHistoryDoesNothingForSameRoute(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');
        $customUrl->addRoute($route);

        // Try to mark route as history pointing to itself
        $customUrl->markRouteAsHistory($route, $route);

        // Should not be marked as history (same UUID check)
        $this->assertFalse($route->isHistory());
    }
}
