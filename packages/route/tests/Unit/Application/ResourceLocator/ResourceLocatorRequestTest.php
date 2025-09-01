<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\ResourceLocator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Symfony\Component\Uid\Uuid;

#[CoversClass(ResourceLocatorRequest::class)]
class ResourceLocatorRequestTest extends TestCase
{
    public function testParts(): void
    {
        $instance = $this->createInstance(parts: ['title' => 'Hello World']);

        $this->assertSame(['title' => 'Hello World'], $instance->parts);
    }

    public function testLocale(): void
    {
        $instance = $this->createInstance(locale: 'de');

        $this->assertSame('de', $instance->locale);
    }

    public function testSite(): void
    {
        $instance = $this->createInstance(site: 'sulu');

        $this->assertSame('sulu', $instance->site);
    }

    public function testResourceKey(): void
    {
        $instance = $this->createInstance(resourceKey: 'articles');

        $this->assertSame('articles', $instance->resourceKey);
    }

    public function testResourceId(): void
    {
        $uuid = Uuid::v7()->toRfc4122();
        $instance = $this->createInstance(resourceId: $uuid);

        $this->assertSame($uuid, $instance->resourceId);
    }

    public function testParentId(): void
    {
        $uuid = Uuid::v7()->toRfc4122();
        $instance = $this->createInstance(parentResourceId: $uuid);

        $this->assertSame($uuid, $instance->parentResourceId);
    }

    public function testParentKey(): void
    {
        $instance = $this->createInstance(parentResourceKey: 'pages');

        $this->assertSame('pages', $instance->parentResourceKey);
    }

    public function testParentKeyFallbackToResourceKey(): void
    {
        $instance = $this->createInstance();

        $this->assertSame('pages', $instance->parentResourceKey);
    }

    public function testRouteSchema(): void
    {
        $instance = $this->createInstance(routeSchema: '/{implode(parts, \'-\')}');

        $this->assertSame('/{implode(parts, \'-\')}', $instance->routeSchema);
    }

    /**
     * @param array<string, string> $parts
     */
    private function createInstance(
        array $parts = [],
        string $locale = 'en',
        ?string $site = null,
        string $resourceKey = 'pages',
        ?string $resourceId = null,
        ?string $parentResourceId = null,
        ?string $parentResourceKey = null,
        ?string $routeSchema = null,
    ): ResourceLocatorRequest {
        return new ResourceLocatorRequest(
            parts: $parts,
            locale: $locale,
            site: $site,
            resourceKey: $resourceKey,
            resourceId: $resourceId,
            parentResourceId: $parentResourceId,
            parentResourceKey: $parentResourceKey,
            routeSchema: $routeSchema,
        );
    }
}
