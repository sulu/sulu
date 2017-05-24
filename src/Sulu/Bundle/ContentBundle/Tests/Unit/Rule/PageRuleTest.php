<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Rule;

use Sulu\Bundle\ContentBundle\Rule\PageRule;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class PageRuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    public function setUp()
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
    }

    /**
     * @dataProvider provideEvaluate
     */
    public function testEvaluate(
        $uuidHeader,
        $uuidValue,
        $urlHeader,
        $urlValue,
        $urlUuidValue,
        $webspaceKey,
        $locale,
        $uuidRule,
        $result
    ) {
        $pageRule = new PageRule(
            $this->requestStack->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->translator->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $uuidHeader,
            $urlHeader
        );

        $requestAttributes = [];
        if ($urlUuidValue) {
            $webspace = null;
            if ($webspaceKey) {
                $webspace = new Webspace();
                $webspace->setKey($webspaceKey);
            }
            $this->requestAnalyzer->getWebspace()->willReturn($webspace);
            $this->requestAnalyzer->getResourceLocator()->willReturn($urlValue);

            $localization = null;
            if ($locale) {
                $localization = new Localization($locale);
            }
            $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

            $resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
            $resourceLocatorStrategy->loadByResourceLocator(
                $urlValue,
                $webspaceKey,
                $locale
            )->willReturn($urlUuidValue);

            $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey($webspaceKey)
                ->willReturn($resourceLocatorStrategy->reveal());
        }

        $request = new Request([], [], $requestAttributes);

        if ($uuidValue) {
            $request->headers->set($uuidHeader, $uuidValue);
        }

        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->assertEquals($result, $pageRule->evaluate(['page' => $uuidRule]));
    }

    public function provideEvaluate()
    {
        return [
            ['X-Forwarded-UUID', 'some-uuid', 'X-Forwarded-URL', null, null, null, null, 'some-uuid', true],
            ['X-UUID', 'some-uuid', 'X-URL', null, null, null, null, 'some-uuid', true],
            ['X-Forwarded-UUID', 'some-uuid', 'X-Forwarded-URL', null, null, null, null, 'some-other-uuid', false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', null, null, null, null, 'some-other-uuid', false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-uuid', true],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/other-test', 'uuid', 'sulu', 'de', 'uuid', true],
            ['X-Forwarded-UUID', null, 'X-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-uuid', true],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', 'en', 'some-other-uuid', false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', 'sulu_io', null, 'some-uuid', false],
            ['X-Forwarded-UUID', null, 'X-Forwarded-URL', '/test', 'some-uuid', null, 'en', 'some-uuid', false],
            ['X-UUID', 'some-uuid', 'X-URL', '/test', 'some-other-uuid', 'sulu_io', 'en', 'some-uuid', true],
            ['X-UUID', 'some-uuid', 'X-URL', '/test', 'some-other-uuid', 'sulu_io', 'en', 'some-other-uuid', false],
        ];
    }
}
