<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolverInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ParameterResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<StructureResolverInterface>
     */
    private $structureResolver;

    /**
     * @var ObjectProphecy<RequestAnalyzerResolverInterface>
     */
    private $requestAnalyzerResolver;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<PageBridge>
     */
    private $structure;

    /**
     * @var ObjectProphecy<Portal>
     */
    private $portal;

    /**
     * @var ObjectProphecy<Webspace>
     */
    private $webspace;

    public function setUp(): void
    {
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->requestAnalyzerResolver = $this->prophesize(RequestAnalyzerResolverInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->structure = $this->prophesize(PageBridge::class);
        $this->portal = $this->prophesize(Portal::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->structure = $this->prophesize(PageBridge::class);
        $this->portal = $this->prophesize(Portal::class);
        $this->webspace = $this->prophesize(Webspace::class);

        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
    }

    public function testResolve(): void
    {
        $parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            '_sulu_segment_switch'
        );

        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en');
        $localization1->getCountry()->willReturn(null);
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de');
        $localization2->getCountry()->willReturn(null);

        $this->structureResolver->resolve($this->structure->reveal(), true)
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'content' => [],
                'view' => [],
                'urls' => [
                    'en' => '/test',
                    'de' => '/test',
                ],
                'extension' => [
                    'seo' => [],
                    'excerpt' => [],
                ],
                'segmentKey' => 'w',
                'webspaceKey' => 'sulu',
            ]);
        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
            ->shouldBeCalledTimes(1)
            ->willReturn(['request' => [], 'webspaceKey' => 'sulu']);
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'en')->willReturn('/en/test');
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'de')->willReturn('/de/test');
        $this->request->getUri()->willReturn('/test');
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal())->shouldBeCalledTimes(1);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization1->reveal());
        $this->portal->getLocalizations()
            ->willReturn([$localization1->reveal(), $localization2->reveal()])->shouldBeCalledTimes(1);

        $segment1 = new Segment();
        $segment1->setKey('s');
        $segment1->setMetadata(['title' => ['en' => 'Summer']]);
        $segment2 = new Segment();
        $segment2->setKey('w');
        $segment2->setMetadata(['title' => ['en' => 'Winter']]);
        $this->webspace->getSegments()->willReturn([$segment1, $segment2]);
        $this->requestAnalyzer->getSegment()->willReturn($segment2);

        $resolvedData = $parameterResolver->resolve(
            [
                'testKey' => 'testValue',
            ],
            $this->requestAnalyzer->reveal(),
            $this->structure->reveal()
        );

        $this->assertEquals([
            'testKey' => 'testValue',
            'content' => [],
            'view' => [],
            'urls' => [
                'en' => '/en/test',
                'de' => '/de/test',
            ],
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'localizations' => [
                'en' => [
                    'locale' => 'en',
                    'url' => '/en/test',
                    'country' => null,
                    'alternate' => true,
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => '/de/test',
                    'country' => null,
                    'alternate' => true,
                ],
            ],
            'segmentKey' => 'w',
            'webspaceKey' => 'sulu',
            'segments' => [
                's' => [
                    'title' => 'Summer',
                    'url' => '_sulu_segment_switch?segment=s&url=/test',
                ],
                'w' => [
                    'title' => 'Winter',
                    'url' => '_sulu_segment_switch?segment=w&url=/test',
                ],
            ],
            'request' => [],
        ], $resolvedData);
    }

    public function testResolveWithoutUrlsParameter(): void
    {
        $parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            '_sulu_segment_switch',
            ['urls' => false]
        );

        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en');
        $localization1->getCountry()->willReturn(null);
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de');
        $localization2->getCountry()->willReturn(null);

        $this->structureResolver->resolve($this->structure->reveal(), true)
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'content' => [],
                'view' => [],
                'urls' => [
                    'en' => '/test',
                    'de' => '/test',
                ],
                'extension' => [
                    'seo' => [],
                    'excerpt' => [],
                ],
            ]);
        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
            ->shouldBeCalledTimes(1)
            ->willReturn(['webspaceKey' => 'sulu']);
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'en')->willReturn('/en/test');
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'de')->willReturn('/de/test');
        $this->request->getUri()->willReturn('/');
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal())->shouldBeCalledTimes(1);
        $this->portal->getLocalizations()
            ->willReturn([$localization1->reveal(), $localization2->reveal()])->shouldBeCalledTimes(1);

        $this->webspace->getSegments()->willReturn([]);

        $resolvedData = $parameterResolver->resolve(
            [
                'testKey' => 'testValue',
            ],
            $this->requestAnalyzer->reveal(),
            $this->structure->reveal()
        );

        $this->assertEquals([
            'testKey' => 'testValue',
            'content' => [],
            'view' => [],
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'localizations' => [
                'en' => [
                    'locale' => 'en',
                    'url' => '/en/test',
                    'country' => null,
                    'alternate' => true,
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => '/de/test',
                    'country' => null,
                    'alternate' => true,
                ],
            ],
            'webspaceKey' => 'sulu',
            'segments' => [],
        ], $resolvedData);
    }

    public function testResolveWithoutUrlsAndCountryParameter(): void
    {
        $parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            '_sulu_segment_switch',
            ['urls' => false]
        );

        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en_gb');
        $localization1->getCountry()->willReturn('gb');
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de_at');
        $localization2->getCountry()->willReturn('at');

        $this->structureResolver->resolve($this->structure->reveal(), true)
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'content' => [],
                'view' => [],
                'urls' => [
                    'en_gb' => '/test',
                    'de_at' => '/test',
                ],
                'extension' => [
                    'seo' => [],
                    'excerpt' => [],
                ],
            ]);
        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
            ->shouldBeCalledTimes(1)
            ->willReturn(['webspaceKey' => 'sulu']);
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'en_gb')->willReturn('/en/test');
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'de_at')->willReturn('/de/test');
        $this->request->getUri()->willReturn('/');
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal())->shouldBeCalledTimes(1);
        $this->portal->getLocalizations()
            ->willReturn([$localization1->reveal(), $localization2->reveal()])->shouldBeCalledTimes(1);

        $this->webspace->getSegments()->willReturn([]);

        $resolvedData = $parameterResolver->resolve(
            [
                'testKey' => 'testValue',
            ],
            $this->requestAnalyzer->reveal(),
            $this->structure->reveal()
        );

        $this->assertEquals([
            'testKey' => 'testValue',
            'content' => [],
            'view' => [],
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'localizations' => [
                'en_gb' => [
                    'locale' => 'en_gb',
                    'url' => '/en/test',
                    'country' => 'gb',
                    'alternate' => true,
                ],
                'de_at' => [
                    'locale' => 'de_at',
                    'url' => '/de/test',
                    'country' => 'at',
                    'alternate' => true,
                ],
            ],
            'webspaceKey' => 'sulu',
            'segments' => [],
        ], $resolvedData);
    }

    public function testResolveLocalizationsAlternate(): void
    {
        $parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            '_sulu_segment_switch',
            ['urls' => false]
        );

        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en_gb');
        $localization1->getCountry()->willReturn('gb');
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de_at');
        $localization2->getCountry()->willReturn('at');

        $this->structureResolver->resolve($this->structure->reveal(), true)
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'content' => [],
                'view' => [],
                'urls' => [
                    'en_gb' => '/test',
                ],
                'extension' => [
                    'seo' => [],
                    'excerpt' => [],
                ],
            ]);
        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
            ->shouldBeCalledTimes(1)
            ->willReturn(['webspaceKey' => 'sulu']);
        $this->webspaceManager->findUrlByResourceLocator('/test', null, 'en_gb')
            ->shouldBeCalled()
            ->willReturn('/en/test');
        $this->webspaceManager->findUrlByResourceLocator('/', null, 'de_at')
            ->shouldBeCalled()
            ->willReturn('/de');
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal())->shouldBeCalledTimes(1);
        $this->request->getUri()->willReturn('/');
        $this->portal->getLocalizations()
            ->willReturn([$localization1->reveal(), $localization2->reveal()])->shouldBeCalledTimes(1);

        $this->webspace->getSegments()->willReturn([]);

        $resolvedData = $parameterResolver->resolve(
            [
                'testKey' => 'testValue',
            ],
            $this->requestAnalyzer->reveal(),
            $this->structure->reveal()
        );

        $this->assertEquals([
            'testKey' => 'testValue',
            'content' => [],
            'view' => [],
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'localizations' => [
                'en_gb' => [
                    'locale' => 'en_gb',
                    'url' => '/en/test',
                    'country' => 'gb',
                    'alternate' => true,
                ],
                'de_at' => [
                    'locale' => 'de_at',
                    'url' => '/de',
                    'country' => 'at',
                    'alternate' => false,
                ],
            ],
            'webspaceKey' => 'sulu',
            'segments' => [],
        ], $resolvedData);
    }
}
