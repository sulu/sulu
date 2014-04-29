<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;

class PortalRouteProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testStateTest()
    {
        // Set up test
        $path = '/';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);
        $localization = new Localization();
        $localization->setLanguage('de');

        $structure = $this->getStructureMock($uuid, 1);
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:error404', $route->getDefaults()['_controller']);
        $this->assertEquals('/', $route->getDefaults()['path']);
    }

    public function testGetCollectionForRequest()
    {
        // Set up test
        $path = '/';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);
        $localization = new Localization();
        $localization->setLanguage('de');

        $structure = $this->getStructureMock($uuid);
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $routes->getIterator()->current()->getDefaults()['structure']->getUuid());
    }

    public function testGetCollectionForNotExistingRequest()
    {
        // Set up test
        $path = '/';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);
        $localization = new Localization();
        $localization->setLanguage('de');

        $structure = $this->getStructureMock($uuid);
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new ResourceLocatorNotFoundException())
        );

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:error404', $route->getDefaults()['_controller']);
        $this->assertEquals('/', $route->getDefaults()['path']);
    }

    public function testGetCollectionForRedirect()
    {
        // Set up test
        $path = '/';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $structure = $this->getStructureMock($uuid);
        $requestAnalyzer = $this->getRequestAnalyzerRedirectMock($portal);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue(null));

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu-redirect.lo', $route->getDefaults()['url']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStructureMock($uuid, $state = 2)
    {
        $structure = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\StructureInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getUuid', 'getGlobalState', 'getHasTranslation')
        );

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));
        $structure->expects($this->any())->method('getGlobalState')->will($this->returnValue($state));
        $structure->expects($this->any())->method('getHasTranslation')->will($this->returnValue(true));

        return $structure;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestAnalyzerMock($portal, $path, $language = null)
    {
        $methods = array('getCurrentPortal', 'getCurrentPath');
        if ($language != null) {
            $methods[] = 'getCurrentLocalization';
        }

        $portalManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Webspace\Analyzer\RequestAnalyzer',
            array(),
            '',
            false,
            true,
            true,
            $methods
        );

        $portalManager->expects($this->any())->method('getCurrentPortal')->will($this->returnValue($portal));
        $portalManager->expects($this->any())->method('getCurrentLocalization')->will($this->returnValue($language));
        $portalManager->expects($this->any())->method('getCurrentPath')->will($this->returnValue($path));

        return $portalManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestAnalyzerRedirectMock($url)
    {
        $portalManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Webspace\Analyzer\RequestAnalyzer',
            array(),
            '',
            false,
            true,
            true,
            array('getCurrentRedirect', 'getCurrentPortalUrl', 'getCurrentMatchType')
        );

        $portalManager->expects($this->any())->method('getCurrentRedirect')->will($this->returnValue('sulu.lo'));
        $portalManager->expects($this->any())->method('getCurrentPortalUrl')->will(
            $this->returnValue('sulu-redirect.lo')
        );
        $portalManager->expects($this->any())->method('getCurrentMatchType')->will(
            $this->returnValue(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT)
        );

        return $portalManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getContentMapperMock()
    {
        $contentMapper = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Mapper\ContentMapperInterface',
            array(),
            '',
            true,
            true,
            true,
            array('loadByResourceLocator')
        );

        return $contentMapper;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getActiveThemeMock()
    {
        return $this->getMock('\Liip\ThemeBundle\ActiveTheme', array(), array(), '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestMock($path)
    {
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request', array('getRequestUri'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue($path));

        return $request;
    }
}
