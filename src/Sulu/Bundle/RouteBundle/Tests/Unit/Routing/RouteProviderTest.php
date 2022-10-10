<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Model\RouteInterface;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;
use Sulu\Bundle\RouteBundle\Routing\RouteProvider;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RouteProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var RouteProvider
     */
    private $routeProvider;

    /**
     * @var ObjectProphecy<RouteRepositoryInterface>
     */
    private $routeRepository;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<RouteDefaultsProviderInterface>
     */
    private $defaultsProvider;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    protected function setUp(): void
    {
        $this->routeRepository = $this->prophesize(RouteRepositoryInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->defaultsProvider = $this->prophesize(RouteDefaultsProviderInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);

        $this->routeProvider = new RouteProvider(
            $this->routeRepository->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->defaultsProvider->reveal(),
            $this->requestStack->reveal()
        );
    }

    public function testGetRouteCollectionForRequestNoRoute(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $this->routeRepository->findByPath('/test', 'de')->willReturn(null);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequestNoFullMatch(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_PARTIAL);

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $this->routeRepository->findByPath('/test', 'de')->shouldNotBeCalled();

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequestNoSupport(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(false);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequestUnpublished(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(false);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequest(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestWithFormat(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test.json');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('json');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test.json', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestWithUmlauts(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn(\rawurlencode('/de/käße'));
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/käße');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/käße', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/käße', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestTwice(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal())->shouldBeCalledTimes(1);
        $this->defaultsProvider->supports('Example')->willReturn(true)->shouldBeCalledTimes(1);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true)->shouldBeCalledTimes(1);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1])->shouldBeCalledTimes(1);

        $this->routeProvider->getRouteCollectionForRequest($request->reveal());
        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestWithHistory(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');
        $request->getQueryString()->willReturn('test=1');
        $request->getSchemeAndHttpHost()->willReturn('http://www.sulu.io');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $targetEntity = $this->prophesize(RouteInterface::class);
        $targetEntity->getPath()->willReturn('/test-2');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->getTarget()->willReturn($targetEntity->reveal());
        $routeEntity->isHistory()->willReturn(true);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(
            ['_controller' => 'sulu_website.redirect_controller::redirectAction', 'url' => 'http://www.sulu.io/de/test-2?test=1'],
            $routes[0]->getDefaults()
        );
    }

    public function testGetRouteCollectionForRequestWithHistoryWithoutQueryString(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');
        $request->getQueryString()->willReturn(null);
        $request->getSchemeAndHttpHost()->willReturn('http://www.sulu.io');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $targetEntity = $this->prophesize(RouteInterface::class);
        $targetEntity->getPath()->willReturn('/test-2');

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->getTarget()->willReturn($targetEntity->reveal());
        $routeEntity->isHistory()->willReturn(true);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(
            ['_controller' => 'sulu_website.redirect_controller::redirectAction', 'url' => 'http://www.sulu.io/de/test-2'],
            $routes[0]->getDefaults()
        );
    }

    public function testGetRouteCollectionForRequestNoPrefix(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn(null);

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestEndingDot(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/test.');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn(null);

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $collection);
    }

    public function testGetRouteCollectionForRequestWithoutFormatExtension(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('json');

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(['test' => 1]);

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
        $this->assertEquals(['test' => 1], $routes[0]->getDefaults());
    }

    public function testGetRouteCollectionForRequestWithOtherSegment(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $webspace = new Webspace();
        $webspace->setKey('webspace');

        $portal = new Portal();
        $portal->setKey('portal');
        $portal->setWebspace($webspace);

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');
        $attributes->getAttribute('portal')->willReturn($portal);

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $routeObject = $this->prophesize(ExtensionBehavior::class);
        $routeObject->getExtensionsData()->willReturn([
            'excerpt' => [
                'segments' => ['webspace' => 'w'],
            ],
        ]);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->requestAnalyzer->changeSegment('w')->shouldBeCalled();

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(
            ['object' => $routeObject->reveal()]
        );

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
    }

    public function testGetRouteCollectionForRequestWithSameSegment(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getPathInfo()->willReturn('/de/test');
        $request->getLocale()->willReturn('de');
        $request->getRequestFormat()->willReturn('html');

        $webspace = new Webspace();
        $webspace->setKey('webspace');

        $portal = new Portal();
        $portal->setKey('portal');
        $portal->setWebspace($webspace);

        $attributes = $this->prophesize(RequestAttributes::class);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');
        $attributes->getAttribute('portal')->willReturn($portal);

        $request->reveal()->attributes = new ParameterBag(['_sulu' => $attributes->reveal()]);

        $routeEntity = $this->prophesize(RouteInterface::class);
        $routeEntity->getEntityClass()->willReturn('Example');
        $routeEntity->getEntityId()->willReturn('1');
        $routeEntity->getId()->willReturn(1);
        $routeEntity->getPath()->willReturn('/test');
        $routeEntity->isHistory()->willReturn(false);
        $routeEntity->getTarget()->willReturn(null);
        $routeEntity->getLocale()->willReturn('de');

        $routeObject = $this->prophesize(ExtensionBehavior::class);
        $routeObject->getExtensionsData()->willReturn([
            'excerpt' => [
                'segments' => ['webspace' => 's'],
            ],
        ]);

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->requestAnalyzer->changeSegment(Argument::cetera())->shouldNotBeCalled();

        $this->routeRepository->findByPath('/test', 'de')->willReturn($routeEntity->reveal());
        $this->defaultsProvider->supports('Example')->willReturn(true);
        $this->defaultsProvider->isPublished('Example', '1', 'de')->willReturn(true);
        $this->defaultsProvider->getByEntity('Example', '1', 'de')->willReturn(
            ['object' => $routeObject->reveal()]
        );

        $collection = $this->routeProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(1, $collection);
        $routes = \array_values(\iterator_to_array($collection->getIterator()));

        $this->assertEquals('/de/test', $routes[0]->getPath());
    }
}
