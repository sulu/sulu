<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content;

use Sulu\Bundle\ContentBundle\Preview\PreviewRenderer;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Response;

class PreviewRendererTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }

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
        $activeTheme = $this->getMockBuilder('Liip\ThemeBundle\ActiveTheme')
            ->disableOriginalConstructor()
            ->getMock();
        $controllerResolver = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $webspaceManager = $this->getMockBuilder('Sulu\Component\Webspace\Manager\WebspaceManager')
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')
            ->disableOriginalConstructor()
            ->getMock();
        $structure = $this->getMockBuilder('Sulu\Component\Content\Compat\Structure\Page')
            ->disableOriginalConstructor()
            ->getMock();

        $webspaceManager->expects($this->any())
            ->method('findWebspaceByKey')
            ->willReturn($this->getWebspace());

        $structure->expects($this->any())
            ->method('getController')
            ->willReturn('TestController:test');

        $controllerResolver->expects($this->once())
            ->method('getController')
            ->will(
                $this->returnCallback(
                    function ($request) {
                        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
                        $this->assertEquals('TestController:test', $request->attributes->get('_controller'));

                        return [new TestController(), 'testAction'];
                    }
                )
            );

        $requestStack->expects($this->once())
            ->method('push')
            ->will(
                $this->returnCallback(
                    function ($request) {
                        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Request', $request);
                        $this->assertEquals('TestController:test', $request->attributes->get('_controller'));
                    }
                )
            );
        $requestStack->expects($this->once())
            ->method('pop');

        $renderer = new PreviewRenderer($activeTheme, $controllerResolver, $webspaceManager, $requestStack);

        $result = $renderer->render($structure);

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
