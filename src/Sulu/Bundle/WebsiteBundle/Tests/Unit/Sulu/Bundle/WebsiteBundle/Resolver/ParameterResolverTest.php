<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use PHPUnit\Framework\TestCase;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
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

    /**
     * @var Localization
     */
    private $localization;

    public function setUp()
    {
        $this->structureResolver = $this->prophesize(StructureResolverInterface::class);
        $this->requestAnalyzerResolver = $this->prophesize(RequestAnalyzerResolver::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->structure = $this->prophesize(PageBridge::class);
        $this->portal = $this->prophesize(Portal::class);
        $this->localization = $this->prophesize(Localization::class);

        $this->parameterResolver = new ParameterResolver(
            $this->structureResolver->reveal(),
            $this->requestAnalyzerResolver->reveal()
        );
    }

    public function testResolve()
    {
        $this->structureResolver->resolve($this->structure->reveal(), true)
            ->shouldBeCalledTimes(1)
            ->willReturn([
                'content' => [],
                'view' => [],
                'urls' => ['en' => '/test'],
                'extension' => [
                    'seo' => [],
                    'excerpt' => [],
                ],
            ]);
        $this->requestAnalyzerResolver->resolve($this->requestAnalyzer)
            ->shouldBeCalledTimes(1)
            ->willReturn(['webspaceKey' => 'sulu']);
        $this->requestAnalyzer->getPortal()->willReturn($this->portal->reveal())->shouldBeCalledTimes(1);
        $this->portal->getLocalizations()->willReturn([$this->localization->reveal()])->shouldBeCalledTimes(1);
        $this->localization->getLocale()->willReturn('en')->shouldBeCalledTimes(1);

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
            'urls' => ['en' => '/test'],
            'extension' => [
                'seo' => [],
                'excerpt' => [],
            ],
            'webspaceKey' => 'sulu',
        ], $resolvedData);
    }
}
