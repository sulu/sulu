<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Sulu\Bundle\WebsiteBundle\Locale\DefaultLocaleProviderInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Theme;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

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

        $structure = $this->getStructureMock($uuid, null, 1);
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testStateTestWithRedirect()
    {
        // Set up test
        $path = '/test/';
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

        $structure = $this->getStructureMock($uuid, null, 1);
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

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

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);
        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $defaults['structure']->getUuid());
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testGetCollectionForRequestWithPartialFlag()
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

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request(['partial' => 'true'], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);
        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals(1, $defaults['structure']->getUuid());
        $this->assertEquals(true, $defaults['partial']);
    }

    public function testGetCollectionForRequestNoLocalization()
    {
        // Set up test
        $path = '';
        $prefix = '/de';
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $contentMapper = $this->getContentMapperMock();
        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix);

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequestNoLocalizationPartial()
    {
        // Set up test
        $path = '';
        $prefix = '/de';
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $contentMapper = $this->getContentMapperMock();
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            null,
            RequestAnalyzerInterface::MATCH_TYPE_PARTIAL
        );

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(
            'SuluWebsiteBundle:Redirect:redirectWebspace',
            array_values(iterator_to_array($routes->getIterator()))[0]->getDefaults()['_controller']
        );
    }

    public function testGetCollectionForRequestNoLocalizationRedirect()
    {
        // Set up test
        $path = '';
        $prefix = '/de';
        $portal = new Portal();
        $portal->setKey('portal');
        $theme = new Theme();
        $theme->setKey('theme');
        $webspace = new Webspace();
        $webspace->setTheme($theme);
        $portal->setWebspace($webspace);

        $contentMapper = $this->getContentMapperMock();
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            null,
            RequestAnalyzerInterface::MATCH_TYPE_REDIRECT
        );

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals(
            'SuluWebsiteBundle:Redirect:redirectWebspace',
            array_values(iterator_to_array($routes->getIterator()))[0]->getDefaults()['_controller']
        );
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
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $localization,
            $path,
            'sulu.lo/de/',
            'sulu.lo/de'
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);
        $urlReplacer->replaceCountry('sulu.lo/de', 'at')->shouldBeCalled()->willReturn('sulu.lo/de');
        $urlReplacer->replaceLanguage('sulu.lo/de', 'de')->shouldBeCalled()->willReturn('sulu.lo/de');
        $urlReplacer->replaceLocalization('sulu.lo/de', 'de-at')->shouldBeCalled()->willReturn('sulu.lo/de');

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo/de/', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo/de', $route->getDefaults()['redirect']);
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

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

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
            'sulu.lo/{localization}'
        );
        $requestAnalyzer->expects($this->any())->method('getAttribute')->will($this->returnValue(null));

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue(null));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);
        $urlReplacer->replaceCountry('sulu.lo/{localization}', 'at')->shouldBeCalled()->willReturn('sulu.lo/{localization}');
        $urlReplacer->replaceLanguage('sulu.lo/{localization}', 'de')->shouldBeCalled()->willReturn('sulu.lo/{localization}');
        $urlReplacer->replaceLocalization('sulu.lo/{localization}', 'de-at')->shouldBeCalled()->willReturn('sulu.lo/de-at');

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
        $this->assertEquals('sulu.lo/de-at', $route->getDefaults()['redirect']);
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

        $requestAnalyzer = $this->getRequestAnalyzerMock($portal, $path, $prefix, $localization);

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new ResourceLocatorNotFoundException())
        );

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

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

        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            new Localization(),
            RequestAnalyzerInterface::MATCH_TYPE_REDIRECT,
            'sulu-redirect.lo',
            'sulu.lo'
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue(null));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);
        $urlReplacer->replaceCountry('sulu.lo', 'at')->shouldBeCalled()->willReturn('sulu.lo');
        $urlReplacer->replaceLanguage('sulu.lo', 'de')->shouldBeCalled()->willReturn('sulu.lo');
        $urlReplacer->replaceLocalization('sulu.lo', 'de-at')->shouldBeCalled()->willReturn('sulu.lo');

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
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

        $structure = $this->getStructureMock(
            $uuid,
            '/other-test',
            Structure::STATE_PUBLISHED,
            Structure::NODE_TYPE_INTERNAL_LINK
        );

        $locale = new Localization();
        $locale->setLanguage('en');
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $locale
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test', $route->getDefaults()['url']);
    }

    public function testGetRedirectForInternalLinkWithQueryString()
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

        $structure = $this->getStructureMock(
            $uuid,
            '/other-test',
            Structure::STATE_PUBLISHED,
            Structure::NODE_TYPE_INTERNAL_LINK
        );

        $locale = new Localization();
        $locale->setLanguage('en');
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $locale
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path, 'QUERY_STRING' => 'test1=value1']);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test?test1=value1', $route->getDefaults()['url']);
    }

    public function testGetRedirectForExternalLink()
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

        $structure = $this->getStructureMock(
            $uuid,
            'http://www.example.org',
            Structure::STATE_PUBLISHED,
            Structure::NODE_TYPE_EXTERNAL_LINK
        );

        $locale = new Localization();
        $locale->setLanguage('en');
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $locale
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will($this->returnValue($structure));

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('http://www.example.org', $route->getDefaults()['url']);
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
            'sulu.lo/de-at'
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->willReturn($structure);

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);
        $urlReplacer->replaceCountry('sulu.lo/de-at', 'at')->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $urlReplacer->replaceLanguage('sulu.lo/de-at', 'de')->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $urlReplacer->replaceLocalization('sulu.lo/de-at', 'de-at')->shouldBeCalled()->willReturn('sulu.lo/de-at');

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
    }

    public function testGetCollectionEndingSlashForHomepage()
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
        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $localization,
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            'sulu.lo',
            'sulu.lo/de-at'
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->willReturn($structure);

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $defaultLocaleProvider->getDefaultLocale()->willReturn(new Localization('de', 'at'));
        $urlReplacer = $this->prophesize(ReplacerInterface::class);
        $urlReplacer->replaceCountry('sulu.lo/de-at', 'at')->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $urlReplacer->replaceLanguage('sulu.lo/de-at', 'de')->shouldBeCalled()->willReturn('sulu.lo/de-at');
        $urlReplacer->replaceLocalization('sulu.lo/de-at', 'de-at')->shouldBeCalled()->willReturn('sulu.lo/de-at');

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirectWebspace', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.lo', $route->getDefaults()['url']);
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

        $requestAnalyzer = $this->getRequestAnalyzerMock(
            $portal,
            $path,
            $prefix,
            $localization,
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            'sulu.lo',
            'sulu.lo/en-us'
        );

        $contentMapper = $this->getContentMapperMock();
        $contentMapper->expects($this->any())->method('loadByResourceLocator')->will(
            $this->throwException(new ResourceLocatorMovedException('/new-test', '123-123-123'))
        );

        $defaultLocaleProvider = $this->prophesize(DefaultLocaleProviderInterface::class);
        $urlReplacer = $this->prophesize(ReplacerInterface::class);

        $portalRouteProvider = new ContentRouteProvider(
            $contentMapper,
            $requestAnalyzer,
            $defaultLocaleProvider->reveal(),
            $urlReplacer->reveal()
        );

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $path]);

        // Test the route provider
        $routes = $portalRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test', $route->getDefaults()['url']);
    }

    /**
     * @return PageBridge
     */
    protected function getStructureMock(
        $uuid,
        $resourceLocator = null,
        $state = Structure::STATE_PUBLISHED,
        $type = Structure::NODE_TYPE_CONTENT
    ) {
        $structure = $this->prophesize(PageBridge::class);

        $structure->getUuid()->willReturn($uuid);
        $structure->getNodeState()->willReturn($state);
        $structure->getNodeType()->willReturn($type);
        $structure->getHasTranslation()->willReturn(true);
        $structure->getController()->willReturn('');
        $structure->getKey()->willReturn('key');
        $structure->getResourceLocator()->willReturn($resourceLocator);

        return $structure->reveal();
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
        $methods = [
            'getPortal',
            'getCurrentPath',
            'getRedirect',
            'getPortalUrl',
            'getMatchType',
            'getResourceLocator',
            'getResourceLocatorPrefix',
            'getAttribute',
            'getCurrentLocalization',
        ];

        $portalManager = $this->getMockForAbstractClass(
            RequestAnalyzer::class,
            [],
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
            ContentMapperInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['loadByResourceLocator']
        );

        return $contentMapper;
    }
}
