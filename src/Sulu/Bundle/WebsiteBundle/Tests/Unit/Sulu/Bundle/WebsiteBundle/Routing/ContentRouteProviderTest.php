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

use PHPCR\RepositoryException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Structure;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Webspace;

class ContentRouteProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testStateTest()
    {
        // Set up test
        $path = '/';
        $prefix = 'de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequest()
    {
        // Set up test
        $path = '';
        $prefix = '/de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $routes->getIterator()->current()->getDefaults()['structure']->getUuid());
    }

    public function testGetCollectionForRequestSlashOnly()
    {
        // Set up test
        $path = '/';
        $prefix = '/de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization, $path);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForSingleLanguageRequestSlashOnly()
    {
        // Set up test
        $path = '/';
        $prefix = '';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization, $path);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $routes->getIterator()->current()->getDefaults()['structure']->getUuid());
    }

    public function testGetCollectionForPartialMatch()
    {
        // Set up test
        $path = '/';
        $prefix = '/de';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $structure = $this->getStructureMock($uuid);
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            null,
            RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
            'sulu.lo',
            'sulu.lo/en-us'
        );
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue(null));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo/en-us', $route->getDefaults()['redirect']);
    }

    public function testGetCollectionForNotExistingRequest()
    {
        // Set up test
        $path = '/';
        $prefix = '/de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new ResourceLocatorNotFoundException())
        );

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRedirect()
    {
        // Set up test
        $path = '/';
        $prefix = '/de';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $structure = $this->getStructureMock($uuid);
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            null,
            RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            'sulu-redirect.lo',
            'sulu.lo'
        );
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue(null));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu-redirect.lo', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['redirect']);
    }

    public function testGetRedirectForInternalLink()
    {
        // Set up test
        $path = '/test';
        $prefix = '/de';
        $uuid = 1;
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $structure = $this->getStructureMock($uuid, Structure::STATE_PUBLISHED, Structure::NODE_TYPE_INTERNAL_LINK);
        $structure->expects($this->any())
            ->method('getResourceLocator')
            ->will($this->returnValue('/other-test'));

        $locale = new Localization();
        $locale->setLanguage('en');
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $locale
        );
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test', $route->getDefaults()['url']);
    }

    public function testGetCollectionEndingSlash()
    {
        // Set up test
        $path = '/qwertz/';
        $prefix = '/de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $localization,
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            'sulu.lo',
            'sulu.lo/en-us'
        );
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new RepositoryException())
        );

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionMovedResourceLocator()
    {
        // Set up test
        $path = '/qwertz/';
        $prefix = '/de';
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
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $localization,
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            'sulu.lo',
            'sulu.lo/en-us'
        );
        $activeTheme = $this->getActiveThemeMock();

        $contentMapper = $this->getContentMapperMock($structure);
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new ResourceLocatorMovedException('/new-test', '123-123-123'))
        );

        $portalRouteProvider = new ContentRouteProvider($contentMapper, $requestAnalyzer, $activeTheme);

        $request = $this->getRequestMock($path);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Default:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test', $route->getDefaults()['url']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStructureMock($uuid, $state = Structure::STATE_PUBLISHED, $type = Structure::NODE_TYPE_CONTENT)
    {
        $structure = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure\Page',
            array(),
            '',
            false,
            true,
            true,
            array('getResourceLocator')
        );

        $structure->setUuid($uuid);
        $structure->setNodeState($state);
        $structure->setNodeType($type);
        $structure->setHasTranslation(true);

        return $structure;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestAnalyzerMock(
        $portal,
        $resourceLocator,
        $resourceLocatorPrefix,
        $language = null,
        $matchType = RequestAnalyzerInterface::MATCH_TYPE_FULL,
        $url = null,
        $redirect = null
    ) {
        $methods = array(
            'getPortal',
            'getCurrentPath',
            'getRedirect',
            'getPortalUrl',
            'getMatchType',
            'getResourceLocator',
            'getResourceLocatorPrefix',
        );

        if ($language != null) {
            $methods[] = 'getCurrentLocalization';
        }

        $portalManager = $this->getMockForAbstractClass(
            '\Sulu\Component\Webspace\Analyzer\WebsiteRequestAnalyzer',
            array(),
            '',
            false,
            true,
            true,
            $methods
        );

        $portalManager->expects($this->any())->method('getPortal')->will($this->returnValue($portal));
        $portalManager->expects($this->any())->method('getCurrentLocalization')->will($this->returnValue($language));
        $portalManager->expects($this->any())->method('getRedirect')->will($this->returnValue($redirect));
        $portalManager->expects($this->any())->method('getPortalUrl')->will($this->returnValue($url));
        $portalManager->expects($this->any())->method('getMatchType')->will($this->returnValue($matchType));
        $portalManager->expects($this->any())->method('getResourceLocator')->will(
            $this->returnValue($resourceLocator)
        );
        $portalManager->expects($this->any())->method('getResourceLocatorPrefix')->will(
            $this->returnValue($resourceLocatorPrefix)
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
            false,
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
     * @param $path
     * @param null $prefix
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRequestMock($path, $prefix = null)
    {
        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request', array('getRequestUri'));
        $request->expects($this->any())->method('getRequestUri')->will($this->returnValue($path));
        $request->expects($this->any())->method('getResourceLocatorPrefix')->will($this->returnValue($prefix));

        return $request;
    }
}
