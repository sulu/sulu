<?php

namespace DTL\Component\Content\Routing;

use DTL\Bundle\ContentBundle\Document\Route;
use DTL\Component\Content\Document\PageInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class PageUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->page = $this->prophesize(PageInterface::class);
        $this->notPage = new \stdClass;
        $this->route1 = $this->prophesize(Route::class);
        $this->sessionManager = $this->prophesize(SessionManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->requestAnalyzer->getPortalUrl()->willReturn('www.example.com/en');

        $this->generator = new PageUrlGenerator(
            $this->sessionManager->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    /**
     * It supports page documents
     */
    public function testSupports()
    {
        $this->assertTrue(
            $this->generator->supports($this->page->reveal())
        );
    }

    /**
     * It doesn't support non-page documents
     */
    public function testSupportsNonPage()
    {
        $this->assertFalse(
            $this->generator->supports($this->notPage)
        );
    }

    /**
     * It returns a router debug message
     */
    public function testRouterDebugMessage()
    {
        $this->assertEquals(
            'stdClass',
            $this->generator->getRouteDebugMessage(new \stdClass)
        );
    }

    /**
     * Non-absolute URL generation is not supported
     *
     * @expectedException BadMethodCallException
     */
    public function testGenerateNonAbsolute()
    {
        $this->generator->generate(
            $this->page->reveal()
        );
    }

    /**
     * It should throw an exception when no routes are associated
     *
     * @expectedException Symfony\Component\Routing\Exception\RouteNotFoundException
     */
    public function testGenerateNoRoutes()
    {
        $this->page->getRoutes()->willReturn(array());
        $this->page->getPath()->willReturn('/page/to');

        $this->generator->generate(
            $this->page->reveal(),
            array(),
            true
        );
    }

    /**
     * It gets localized route if no cache
     */
    public function testGenerateLocalized()
    {
        $routesPath = '/cms/path/routes/en';
        $routePath = 'foobar/barfoo';
        $expectedUrl = 'http://www.example.com/en/foobar/barfoo';

        $this->page->getRoutes()->willReturn(array(
            $this->route1->reveal()
        ));
        $this->route1->getAutoRouteTag()->willReturn('fr');
        $this->page->getLocale()->willReturn('fr');
        $this->page->getWebspaceKey()->willReturn('test');
        $this->sessionManager->getRoutePath('test', 'fr')->willReturn($routesPath);
        $this->route1->getPath()->willReturn($routesPath . '/' . $routePath);

        $result = $this->generator->generate(
            $this->page->reveal(),
            array(),
            true
        );

        $this->assertEquals($expectedUrl, $result);
    }

    /**
     * It returns any route if no no localization found
     */
    public function testGenerateNoLocalized()
    {
        $routesPath = '/cms/path/routes/en';
        $routePath = 'foobar/barfoo';
        $expectedUrl = 'http://www.example.com/en/foobar/barfoo';

        $this->page->getRoutes()->willReturn(array(
            $this->route1->reveal()
        ));
        $this->route1->getAutoRouteTag()->willReturn('de');
        $this->page->getLocale()->willReturn('fr');
        $this->page->getWebspaceKey()->willReturn('test');
        $this->sessionManager->getRoutePath('test', 'fr')->willReturn($routesPath);
        $this->route1->getPath()->willReturn($routesPath . '/' . $routePath);

        $result = $this->generator->generate(
            $this->page->reveal(),
            array(),
            true
        );

        $this->assertEquals($expectedUrl, $result);
    }
}
