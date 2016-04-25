<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content;

use Liip\ThemeBundle\ActiveTheme;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Preview\PreviewRenderer;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class PreviewRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ActiveTheme
     */
    private $activeTheme;

    /**
     * @var ControllerResolver
     */
    private $controllerResolver;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var PageBridge
     */
    private $structure;

    /**
     * @var PreviewRenderer
     */
    private $previewRenderer;

    public function setUp()
    {
        $this->activeTheme = $this->prophesize(ActiveTheme::class);
        $this->controllerResolver = $this->prophesize(ControllerResolver::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->translator = $this->prophesize(TranslatorInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->controllerResolver->getController(Argument::type(Request::class))
            ->will(
                function () {
                    return [new TestController(), 'testAction'];
                }
            );

        $this->webspace = new Webspace();
        $this->webspace->setName('test');
        $theme = new Theme();
        $theme->setKey('default');
        $this->webspace->setTheme($theme);

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($this->webspace);

        $this->structure = $this->prophesize(PageBridge::class);
        $this->structure->getController()->willReturn('TestController:test');
        $this->structure->getLanguageCode()->willReturn('de_at');
        $this->structure->getWebspaceKey()->willReturn('sulu_io');

        $this->translator->getLocale()->willReturn('de');
        $this->translator->setLocale('de_at')->shouldBeCalled();
        $this->translator->setLocale('de')->shouldBeCalled();

        $this->previewRenderer = new PreviewRenderer(
            $this->activeTheme->reveal(),
            $this->controllerResolver->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack->reveal(),
            $this->translator->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    public function testRender()
    {
        $request = new Request(['test' => 1], ['test' => 2], [], ['test' => 3]);

        $this->requestStack->getCurrentRequest()->willReturn($request);
        $this->requestStack->push(
            Argument::that(
                function (Request $newRequest) use ($request) {
                    $this->assertEquals(
                        array_merge(
                            ['webspace' => 'sulu_io', 'locale' => 'de_at'],
                            $request->query->all()
                        ),
                        $newRequest->query->all()
                    );
                    $this->assertEquals($request->request->all(), $newRequest->request->all());
                    $this->assertEquals($request->cookies->all(), $newRequest->cookies->all());

                    return true;
                }
            )
        )->shouldBeCalledTimes(1);
        $this->requestStack->pop()->shouldBeCalled();

        $result = $this->previewRenderer->render($this->structure->reveal());

        $this->assertEquals('TEST', $result);
    }

    public function testRenderWithoutCurrentRequest()
    {
        $this->requestStack->getCurrentRequest()->willReturn(null);
        $this->requestStack->push(
            Argument::that(
                function (Request $newRequest) {
                    $this->assertEquals(['webspace' => 'sulu_io', 'locale' => 'de_at'], $newRequest->query->all());
                    $this->assertEquals([], $newRequest->request->all());
                    $this->assertEquals([], $newRequest->cookies->all());

                    return true;
                }
            )
        )->shouldBeCalledTimes(1);
        $this->requestStack->pop()->shouldBeCalled();

        $result = $this->previewRenderer->render($this->structure->reveal());

        $this->assertEquals('TEST', $result);
    }
}

class TestController
{
    public function testAction()
    {
        return new Response('TEST');
    }
}
