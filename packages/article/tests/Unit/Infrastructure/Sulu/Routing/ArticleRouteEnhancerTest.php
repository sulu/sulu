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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Application\Webspace\WebspaceResolver;
use Sulu\Article\Domain\Model\AdditionalWebspacesInterface;
use Sulu\Article\Infrastructure\Sulu\Routing\ArticleRouteEnhancer;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;

class ArticleRouteEnhancerTest extends TestCase
{
    use ProphecyTrait;

    private WebspaceManagerInterface $webspaceManager;
    private WebspaceResolver $webspaceResolver;

    protected function setUp(): void
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->webspaceResolver = $this->prophesize(WebspaceResolver::class);
    }

    protected function getArticleRouteEnhancerInstance(): RouteEnhancerInterface
    {
        return new ArticleRouteEnhancer(
            $this->webspaceManager->reveal(),
            $this->webspaceResolver->reveal(),
            'test'
        );
    }

    public function testEnhanceWithoutObject(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $defaults = ['_controller' => 'TestController::indexAction'];
        $request = new Request();

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithNonRoutableObject(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = new \stdClass();
        $defaults = ['object' => $object];
        $request = new Request();

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithoutSuluAttributes(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);

        $defaults = ['object' => $object->reveal()];
        $request = new Request();

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithoutWebspace(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspace')->willReturn(null);

        $defaults = ['object' => $object->reveal()];
        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes->reveal());

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithoutAdditionalWebspaces(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getLocale()->willReturn('en');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu-io');

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspace')->willReturn($webspace->reveal());

        $this->webspaceResolver->resolveAdditionalWebspaces($object->reveal(), 'en')
            ->willReturn([]); // No additional webspaces

        $defaults = ['object' => $object->reveal()];
        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes->reveal());

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithWebspaceNotInAdditionalWebspaces(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getLocale()->willReturn('en');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu-io');

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspace')->willReturn($webspace->reveal());

        $this->webspaceResolver->resolveAdditionalWebspaces($object->reveal(), 'en')
            ->willReturn(['other-webspace']); // Different webspace

        $defaults = ['object' => $object->reveal()];
        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes->reveal());

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }

    public function testEnhanceWithValidCanonicalUrl(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $locale = 'en';
        $slug = '/test-article';
        $mainWebspace = 'main-webspace';
        $canonicalUrl = 'https://example.com/test-article';

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getLocale()->willReturn($locale);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu-io');

        $route = $this->prophesize(Route::class);
        $route->getSlug()->willReturn($slug);

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspace')->willReturn($webspace->reveal());

        $this->webspaceResolver->resolveAdditionalWebspaces($object->reveal(), $locale)
            ->willReturn(['sulu-io']); // Webspace is in additional webspaces

        $this->webspaceResolver->resolveMainWebspace($object->reveal(), $locale)
            ->willReturn($mainWebspace);

        $this->webspaceManager->findUrlByResourceLocator($slug, 'test', $locale, $mainWebspace)
            ->willReturn($canonicalUrl);

        $defaults = ['object' => $object->reveal()];
        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes->reveal());
        $request->attributes->set('_sulu_route', $route->reveal());

        $result = $enhancer->enhance($defaults, $request);

        $expected = [
            'object' => $object->reveal(),
            '_seo' => [
                'canonicalUrl' => $canonicalUrl,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testEnhanceWithoutRoute(): void
    {
        $enhancer = $this->getArticleRouteEnhancerInstance();

        $object = $this->prophesize();
        $object->willImplement(RoutableInterface::class);
        $object->willImplement(AdditionalWebspacesInterface::class);
        $object->getLocale()->willReturn('en');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu-io');

        $requestAttributes = $this->prophesize(RequestAttributes::class);
        $requestAttributes->getAttribute('webspace')->willReturn($webspace->reveal());

        $this->webspaceResolver->resolveAdditionalWebspaces($object->reveal(), 'en')
            ->willReturn(['sulu-io']);

        $this->webspaceResolver->resolveMainWebspace($object->reveal(), 'en')
            ->willReturn('main-webspace');

        $defaults = ['object' => $object->reveal()];
        $request = new Request();
        $request->attributes->set('_sulu', $requestAttributes->reveal());
        // No _sulu_route attribute

        $result = $enhancer->enhance($defaults, $request);

        $this->assertSame($defaults, $result);
    }
}