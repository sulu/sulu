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
    private $requestAnalyzer;

    /**
     * @var PageBridge
     */
    private $structure;

    /**
     * @var ParameterResolver
     */
    private $parameterResolver;

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

        $this->parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal(),
            $this->webspaceManager->reveal()
        );
    }

    public function testResolve()
    {
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

        $resolvedData = $this->parameterResolver->resolve(
            [
                'testKey' => 'testValue',
            ],
            $this->requestAnalyzer->reveal(),
            $this->structure->reveal()
        );

        $this->assertSame([
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
            'webspaceKey' => 'sulu',
        ], $resolvedData);
    }
}
