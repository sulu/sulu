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
use Symfony\Component\Uid\Uuid;

class CustomUrlRouteTest extends TestCase
{
    public function testConstructorSetsCustomUrlAndPath(): void
    {
        $customUrl = new CustomUrl();
        $path = 'example.com/test-path';

        $route = new CustomUrlRoute($customUrl, $path);

        $this->assertSame($customUrl, $route->getCustomUrl());
        $this->assertSame($path, $route->getPath());
    }

    public function testConstructorGeneratesUuidV7(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $uuid = $route->getUuid();

        // UUID v7 format: xxxxxxxx-xxxx-7xxx-xxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testUuidIsUniqueForEachRoute(): void
    {
        $customUrl = new CustomUrl();
        $route1 = new CustomUrlRoute($customUrl, 'example.com/route1');
        $route2 = new CustomUrlRoute($customUrl, 'example.com/route2');

        $this->assertNotSame($route1->getUuid(), $route2->getUuid());
    }

    public function testSetUuidOverridesGeneratedUuid(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $customUuid = Uuid::v4()->toRfc4122();
        $route->setUuid($customUuid);

        $this->assertSame($customUuid, $route->getUuid());
    }

    public function testHistoryDefaultsToFalse(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $this->assertFalse($route->isHistory());
    }

    public function testSetHistoryToTrue(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $route->setHistory(true);

        $this->assertTrue($route->isHistory());
    }

    public function testSetHistoryToFalse(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $route->setHistory(true);
        $route->setHistory(false);

        $this->assertFalse($route->isHistory());
    }

    public function testTargetRouteDefaultsToNull(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $this->assertNull($route->getTargetRoute());
    }

    public function testSetTargetRoute(): void
    {
        $customUrl = new CustomUrl();
        $oldRoute = new CustomUrlRoute($customUrl, 'example.com/old');
        $newRoute = new CustomUrlRoute($customUrl, 'example.com/new');

        $oldRoute->setTargetRoute($newRoute);

        $this->assertSame($newRoute, $oldRoute->getTargetRoute());
    }

    public function testSetTargetRouteToNull(): void
    {
        $customUrl = new CustomUrl();
        $oldRoute = new CustomUrlRoute($customUrl, 'example.com/old');
        $newRoute = new CustomUrlRoute($customUrl, 'example.com/new');

        $oldRoute->setTargetRoute($newRoute);
        $oldRoute->setTargetRoute(null);

        $this->assertNull($oldRoute->getTargetRoute());
    }

    public function testSetTargetRouteWithDifferentCustomUrl(): void
    {
        $customUrl1 = new CustomUrl();
        $customUrl2 = new CustomUrl();

        $route1 = new CustomUrlRoute($customUrl1, 'example.com/route1');
        $route2 = new CustomUrlRoute($customUrl2, 'example.com/route2');

        // This should work - the route doesn't validate that they belong to same custom URL
        $route1->setTargetRoute($route2);

        $this->assertSame($route2, $route1->getTargetRoute());
    }

    public function testHistoryRouteWithTargetRoute(): void
    {
        $customUrl = new CustomUrl();
        $historyRoute = new CustomUrlRoute($customUrl, 'example.com/old');
        $currentRoute = new CustomUrlRoute($customUrl, 'example.com/new');

        $historyRoute->setHistory(true);
        $historyRoute->setTargetRoute($currentRoute);

        $this->assertTrue($historyRoute->isHistory());
        $this->assertSame($currentRoute, $historyRoute->getTargetRoute());
        $this->assertFalse($currentRoute->isHistory());
    }

    public function testRouteChainWithMultipleTargets(): void
    {
        $customUrl = new CustomUrl();

        $route1 = new CustomUrlRoute($customUrl, 'example.com/v1');
        $route2 = new CustomUrlRoute($customUrl, 'example.com/v2');
        $route3 = new CustomUrlRoute($customUrl, 'example.com/v3');

        $route1->setHistory(true);
        $route1->setTargetRoute($route2);

        $route2->setHistory(true);
        $route2->setTargetRoute($route3);

        // Verify chain: v1 -> v2 -> v3
        $this->assertTrue($route1->isHistory());
        $this->assertSame($route2, $route1->getTargetRoute());

        $this->assertTrue($route2->isHistory());
        $this->assertSame($route3, $route2->getTargetRoute());

        $this->assertFalse($route3->isHistory());
        $this->assertNull($route3->getTargetRoute());
    }

    public function testPathWithComplexDomainPattern(): void
    {
        $customUrl = new CustomUrl();

        $paths = [
            'subdomain.example.com/path/to/resource',
            '*.example.com/wildcard',
            'example.com/*/dynamic/*',
            'api.v2.example.com/endpoint',
        ];

        foreach ($paths as $path) {
            $route = new CustomUrlRoute($customUrl, $path);
            $this->assertSame($path, $route->getPath());
        }
    }

    public function testPathWithSpecialCharacters(): void
    {
        $customUrl = new CustomUrl();

        $paths = [
            'example.com/path-with-dashes',
            'example.com/path_with_underscores',
            'example.com/path.with.dots',
            'example.com/path/with/slashes',
            'example.com/MixedCase',
        ];

        foreach ($paths as $path) {
            $route = new CustomUrlRoute($customUrl, $path);
            $this->assertSame($path, $route->getPath());
        }
    }

    public function testMultipleRoutesWithSamePath(): void
    {
        $customUrl = new CustomUrl();
        $path = 'example.com/duplicate';

        $route1 = new CustomUrlRoute($customUrl, $path);
        $route2 = new CustomUrlRoute($customUrl, $path);

        // Both routes should have the same path but different UUIDs
        $this->assertSame($path, $route1->getPath());
        $this->assertSame($path, $route2->getPath());
        $this->assertNotSame($route1->getUuid(), $route2->getUuid());
    }

    public function testHistoryFlagIndependentOfTargetRoute(): void
    {
        $customUrl = new CustomUrl();
        $route1 = new CustomUrlRoute($customUrl, 'example.com/route1');
        $route2 = new CustomUrlRoute($customUrl, 'example.com/route2');

        // Set target route without setting history
        $route1->setTargetRoute($route2);
        $this->assertFalse($route1->isHistory());

        // Set history without target route
        $route2->setHistory(true);
        $this->assertNull($route2->getTargetRoute());
    }

    public function testUuidPersistenceAfterModifications(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/test');

        $originalUuid = $route->getUuid();

        // Modify route properties
        $route->setHistory(true);
        $route->setTargetRoute(new CustomUrlRoute($customUrl, 'example.com/other'));

        // UUID should remain unchanged
        $this->assertSame($originalUuid, $route->getUuid());
    }

    public function testRouteWithMinimalPath(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'a');

        $this->assertSame('a', $route->getPath());
    }

    public function testRouteWithVeryLongPath(): void
    {
        $customUrl = new CustomUrl();
        $longPath = 'example.com/' . \str_repeat('segment/', 50) . 'end';

        $route = new CustomUrlRoute($customUrl, $longPath);

        $this->assertSame($longPath, $route->getPath());
    }

    public function testCircularTargetRouteReference(): void
    {
        $customUrl = new CustomUrl();
        $route1 = new CustomUrlRoute($customUrl, 'example.com/route1');
        $route2 = new CustomUrlRoute($customUrl, 'example.com/route2');

        // Create circular reference
        $route1->setTargetRoute($route2);
        $route2->setTargetRoute($route1);

        // Should work - the entity doesn't prevent circular references
        $this->assertSame($route2, $route1->getTargetRoute());
        $this->assertSame($route1, $route2->getTargetRoute());
    }

    public function testSelfReferencingTargetRoute(): void
    {
        $customUrl = new CustomUrl();
        $route = new CustomUrlRoute($customUrl, 'example.com/self');

        // Route pointing to itself
        $route->setTargetRoute($route);

        $this->assertSame($route, $route->getTargetRoute());
    }

    public function testUsesTimestampableTrait(): void
    {
        // Verify that the CustomUrlRoute class uses the TimestampableTrait
        $reflectionClass = new \ReflectionClass(CustomUrlRoute::class);
        $traitNames = $reflectionClass->getTraitNames();

        $this->assertContains(
            'Sulu\Component\Persistence\Model\TimestampableTrait',
            $traitNames,
            'CustomUrlRoute should use TimestampableTrait'
        );
    }
}
