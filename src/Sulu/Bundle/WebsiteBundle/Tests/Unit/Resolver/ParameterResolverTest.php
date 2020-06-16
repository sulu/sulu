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
use Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\RequestAnalyzerResolver;
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
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var RequestAnalyzerResolverInterface
     */
    private $requestAnalyzerResolver;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var PageBridge
     */
    private $structure;

    /**
     * @var Portal
     */
    private $portal;

    public function setUp(): void
    {
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->requestAnalyzerResolver = $this->prophesize(RequestAnalyzerResolver::class);
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
        $this->localization = $this->prophesize(Localization::class);

        $this->requestStack->getCurrentRequest()->willReturn($this->request->reveal());
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
    }

    public function testResolve()
    {
        $parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            '_sulu_segment_switch'
        );

        $localization1 = $this->prophesize(Localization::class);
        $localization1->getLocale()->willReturn('en')->shouldBeCalledTimes(1);
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de')->shouldBeCalledTimes(1);

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
        $this->portal->getLocalizations()
            ->willReturn([$localization1->reveal(), $localization2->reveal()])->shouldBeCalledTimes(1);

        $segment1 = new Segment();
        $segment1->setKey('s');
        $segment2 = new Segment();
        $segment2->setKey('w');
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
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => '/de/test',
                ],
            ],
            'segmentKey' => 'w',
            'webspaceKey' => 'sulu',
            'segmentUrls' => [
                's' => '_sulu_segment_switch?segment=s&url=/test',
                'w' => '_sulu_segment_switch?segment=w&url=/test',
            ],
            'request' => [],
        ], $resolvedData);
    }

    public function testResolveWithoutUrlsParameter()
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
        $localization1->getLocale()->willReturn('en')->shouldBeCalledTimes(1);
        $localization2 = $this->prophesize(Localization::class);
        $localization2->getLocale()->willReturn('de')->shouldBeCalledTimes(1);

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
                ],
                'de' => [
                    'locale' => 'de',
                    'url' => '/de/test',
                ],
            ],
            'webspaceKey' => 'sulu',
            'segmentUrls' => [],
        ], $resolvedData);
    }
}
