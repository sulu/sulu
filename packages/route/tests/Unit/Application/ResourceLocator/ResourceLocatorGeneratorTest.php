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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanup;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorGenerator;
use Sulu\Route\Application\ResourceLocator\ResourceLocatorRequest;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[CoversClass(ResourceLocatorGenerator::class)]
class ResourceLocatorGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private ObjectProphecy $routeRepository;

    private ResourceLocatorGenerator $resourceLocatorGenerator;

    public function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);

        $this->resourceLocatorGenerator = new ResourceLocatorGenerator(
            $this->routeRepository->reveal(),
            new PathCleanup(new AsciiSlugger(), []),
        );
    }

    public function testGenerate(): void
    {
        $request = $this->createResourceLocatorRequest(
            parts: ['title' => 'Hello World'],
        );

        $this->routeRepository->existBy(Argument::any())->willReturn(false);

        $this->assertSame('/hello-world', $this->resourceLocatorGenerator->generate($request));
    }

    public function testGenerateWithParent(): void
    {
        $request = $this->createResourceLocatorRequest(
            parts: [
                'title' => 'Hello World',
            ],
            locale: 'de',
            site: 'website',
            resourceKey: 'articles',
            parentResourceId: 'cff165a7-ae8e-46b4-8f32-f0a339173207',
            parentResourceKey: 'pages',
        );

        $parentRoute = $this->createRoute(
            resourceKey: 'pages',
            resourceId: 'cff165a7-ae8e-46b4-8f32-f0a339173207',
            locale: 'de',
            slug: '/news',
            site: 'website',
        );
        $this->routeRepository->findOneBy([
            'resourceId' => 'cff165a7-ae8e-46b4-8f32-f0a339173207',
            'resourceKey' => 'pages',
            'locale' => 'de',
            'site' => 'website',
        ])
            ->willReturn($parentRoute)
            ->shouldBeCalled();

        $this->routeRepository->existBy(Argument::any())->willReturn(false);

        $this->assertSame('/news/hello-world', $this->resourceLocatorGenerator->generate($request));
    }

    public function testGenerateAlreadyExists(): void
    {
        $request = $this->createResourceLocatorRequest(
            parts: ['title' => 'Hello World'],
        );

        $this->routeRepository->existBy([
            'slug' => '/hello-world',
            'locale' => 'en',
            'site' => null,
        ])->willReturn(true);

        $this->routeRepository->existBy([
            'slug' => '/hello-world-1',
            'locale' => 'en',
            'site' => null,
        ])->willReturn(true);

        $this->routeRepository->existBy([
            'slug' => '/hello-world-2',
            'locale' => 'en',
            'site' => null,
        ])->willReturn(false);

        $this->assertSame('/hello-world-2', $this->resourceLocatorGenerator->generate($request));
    }

    /**
     * @param array<string, string> $parts
     */
    private function createResourceLocatorRequest(
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

    public function createRoute(
        string $resourceKey = 'resource',
        string $resourceId = '12345',
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
}
