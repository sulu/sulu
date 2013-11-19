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
use Sulu\Component\Portal\Portal;
use Sulu\Component\Portal\Theme;
use Symfony\Component\HttpFoundation\Request;

class PortalRouteProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCollectionForRequest()
    {
        // Set up test
        $path = '/';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $portal->setTheme($theme);

        $structure = $this->getStructureMock($uuid);
        $portalManager = $this->getPortalManagerMock($portal);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $portalManager, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $routes->getIterator()->current()->getDefaults()['content']->getUuid());
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
        $portal->setTheme($theme);

        $structure = $this->getStructureMock($uuid);
        $portalManager = $this->getPortalManagerMock($portal);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->throwException(new ResourceLocatorNotFoundException()));

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $portalManager, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:error404', $route->getDefaults()['_controller']);
        $this->assertEquals('/', $route->getDefaults()['path']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStructureMock($uuid)
    {
        $structure = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\StructureInterface',
            array(),
            '',
            true,
            true,
            true,
            array('getUuid')
        );

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($uuid));

        return $structure;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPortalManagerMock($portal)
    {
        $portalManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Portal\PortalManagerInterface',
            array(),
            '',
            true,
            true,
            true,
            array('findByUrl', 'getCurrentPortal')
        );

        $portalManager->expects($this->any())->method('findByUrl')->will($this->returnValue($portal));
        $portalManager->expects($this->any())->method('getCurrentPortal')->will($this->returnValue($portal));

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
