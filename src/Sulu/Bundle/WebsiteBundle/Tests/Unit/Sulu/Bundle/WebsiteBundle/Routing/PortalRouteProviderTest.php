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

        $structure->expects($this->any())->method('getUuid')->will($this->returnValue($structure));

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

        $contentMapper = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Mapper\ContentMapperInterface',
            array(),
            '',
            true,
            true,
            true,
            array('loadByResourceLocator')
        );
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $activeTheme = $this->getMock('\Liip\ThemeBundle\ActiveTheme', array(), array(), '', false);

        $portalRouteProvider = new PortalRouteProvider($contentMapper, $portalManager, $activeTheme);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request', array('getRequestUri'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue($path));

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertEquals(1, $routes->getIterator()->current()->getDefaults()['content']->getUuid());
    }
}
