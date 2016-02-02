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
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class PreviewRendererTest extends \PHPUnit_Framework_TestCase
{
    private function getWebspace()
    {
        $webspace = new Webspace();
        $webspace->setName('test');

        $theme = new Theme();
        $theme->setKey('default');
        $webspace->setTheme($theme);

        return $webspace;
    }

    public function testRender()
    {
        $request = new Request(['test' => 1], ['test' => 2], [], ['test' => 3]);

        $activeTheme = $this->prophesize(ActiveTheme::class);
        $controllerResolver = $this->prophesize(ControllerResolver::class);
        $webspaceManager = $this->prophesize(WebspaceManager::class);
        $requestStack = $this->prophesize(RequestStack::class);
        $structure = $this->prophesize(PageBridge::class);
        $translator = $this->prophesize(TranslatorInterface::class);

        $webspaceManager->findWebspaceByKey('sulu_io')->willReturn($this->getWebspace());

        $structure->getController()->willReturn('TestController:test');
        $structure->getLanguageCode()->willReturn('de_at');
        $structure->getWebspaceKey()->willReturn('sulu_io');

        $controllerResolver->getController(Argument::type(Request::class))
            ->will(
                function () {
                    return [new TestController(), 'testAction'];
                }
            );

        $requestStack->getCurrentRequest()->willReturn($request);
        $requestStack->push(
            Argument::that(
                function (Request $newRequest) use ($request) {
                    $this->assertEquals($request->query->all(), $newRequest->query->all());
                    $this->assertEquals($request->request->all(), $newRequest->request->all());
                    $this->assertEquals($request->cookies->all(), $newRequest->cookies->all());

                    return true;
                }
            )
        )->shouldBeCalledTimes(1);
        $requestStack->pop()->shouldBeCalled();

        $translator->getLocale()->willReturn('de');
        $translator->setLocale('de_at')->shouldBeCalled();
        $translator->setLocale('de')->shouldBeCalled();

        $renderer = new PreviewRenderer(
            $activeTheme->reveal(),
            $controllerResolver->reveal(),
            $webspaceManager->reveal(),
            $requestStack->reveal(),
            $translator->reveal()
        );

        $result = $renderer->render($structure->reveal());

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
