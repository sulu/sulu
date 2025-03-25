<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\Uid\Uuid;

class RouteTest extends TestCase
{
    use SetGetPrivatePropertyTrait;

    public function testGetResourceKey(): void
    {
        $route = $this->createModel(resourceKey: 'test');

        $this->assertEquals('test', $route->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $uuid = Uuid::v7()->toRfc4122();

        $route = $this->createModel(resourceId: $uuid);

        $this->assertEquals($uuid, $route->getResourceId());
    }

    public function testSetGetSlug(): void
    {
        $slug = '/test';
        $route = $this->createModel(slug: $slug);
        $this->assertSame('/test', $route->getSlug());
        $route->setSlug('/test2');
        $this->assertSame('/test2', $route->getSlug());
    }

    public function testGetParentRoute(): void
    {
        $parentRoute = $this->createModel(slug: '/test');
        $route = $this->createModel(slug: '/test/child', parentRoute: $parentRoute);

        $this->assertSame($parentRoute, $route->getParentRoute());
    }

    public function testSetSlugViaLeafEdit(): void
    {
        $parentRoute = $this->createModel(slug: '/test');
        $route = $this->createModel(slug: '/test/child', parentRoute: $parentRoute);

        $route->setSlug('/test/child-2');
        $this->assertSame('/test/child-2', $route->getSlug());
        $this->assertSame($parentRoute, $route->getParentRoute());
    }

    public function testSetSlugViaFullTreeEdit(): void
    {
        $parentRoute = $this->createModel(slug: '/test');
        $route = $this->createModel(slug: '/test/child', parentRoute: $parentRoute);

        $route->setSlug('/test2');
        $this->assertSame('/test2', $route->getSlug());
        $this->assertSame($parentRoute, $route->getParentRoute(), 'We keep connection to parent route for easier opt-in later.');
    }

    public function testWithTempId(): void
    {
        $counter = 0;
        $route = $this->createModelWithTempId(
            resourceIdCallable: function() use (&$counter) {
                return (string) ++$counter;
            },
        );

        $this->assertStringStartsWith('temp::', $route->getResourceId());
        $this->assertTrue($route->hasTemporaryId());
        $this->assertSame(0, $counter);
        $newResourceId = $route->generateRealResourceId();
        $this->assertSame('1', $newResourceId);
        $this->assertSame(1, $counter); // @phpstan-ignore-line method.impossibleType
        static::setPrivateProperty($route, 'resourceId', $newResourceId);
        $this->assertFalse($route->hasTemporaryId());
        $this->assertSame($newResourceId, $route->getResourceId());
    }

    public function createModel(
        string $resourceKey = 'resource',
        string $resourceId = '1',
        string $locale = 'en',
        string $slug = '/',
        ?string $site = null,
        ?Route $parentRoute = null,
    ): Route {
        return new Route(
            $resourceKey,
            $resourceId,
            $locale,
            $slug,
            $site,
            $parentRoute,
        );
    }

    public function createModelWithTempId(
        string $resourceKey = 'resource',
        ?callable $resourceIdCallable = null,
        string $locale = 'en',
        string $slug = '/',
        ?string $site = null,
        ?Route $parentRoute = null,
    ): Route {
        return Route::createRouteWithTempId(
            $resourceKey,
            null === $resourceIdCallable ? fn () => '1' : $resourceIdCallable,
            $locale,
            $slug,
            $site,
            $parentRoute,
        );
    }
}
