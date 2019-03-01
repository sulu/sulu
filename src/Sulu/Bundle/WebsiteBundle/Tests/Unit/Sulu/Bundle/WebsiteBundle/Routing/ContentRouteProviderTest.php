<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Routing;

use Prophecy\Argument;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ContentRouteProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @var ResourceLocatorStrategyInterface
     */
    private $resourceLocatorStrategy;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var ContentRouteProvider
     */
    private $contentRouteProvider;

    public function setUp()
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(Argument::any())->willReturn($this->resourceLocatorStrategy->reveal());

        $this->contentRouteProvider = new ContentRouteProvider(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $this->structureManager->reveal(),
            $this->requestAnalyzer->reveal()
        );
    }

    public function testStateTest()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => rawurlencode('/')]);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequest()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $localization = new Localization();
        $localization->setLanguage('de');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn(null);
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(
            [],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [],
            ['REQUEST_URI' => rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testGetCollectionForRequestWithUmlauts()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $localization = new Localization();
        $localization->setLanguage('de');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/käße');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('/käße', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)->willImplement(RedirectTypeBehavior::class)->willImplement(
                StructureBehavior::class
            )->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn(
            $document->reveal()
        );

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(
            [],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [],
            ['REQUEST_URI' => rawurlencode('/de/käße')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        /** @var Route $route */
        $route = $routes->getIterator()->current();
        $defaults = $route->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
        $this->assertEquals('/de/käße', $route->getPath());
    }

    public function testGetCollectionForRequestWithMissingStructure()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn(null);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => rawurlencode('/')]);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequestWithPartialFlag()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $localization = new Localization();
        $localization->setLanguage('de');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn(null);
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(
            ['partial' => 'true'],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [],
            ['REQUEST_URI' => rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(true, $defaults['partial']);
    }

    public function testGetCollectionForRequestNoLocalization()
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getCurrentLocalization()->willReturn(null);
        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => rawurlencode('/')]);

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForNotExistingRequest()
    {
        $localization = new Localization();
        $localization->setLanguage('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $this->requestAnalyzer->getPortal()->willReturn($portal);

        $this->requestAnalyzer->getMatchType()->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $this->requestAnalyzer->getResourceLocator()->willReturn('/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')
            ->willThrow(ResourceLocatorNotFoundException::class);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => rawurlencode('/')]);
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetRedirectForInternalLink()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('localization', null)->willReturn(new Localization('de'));

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/de/test')]
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test', $route->getDefaults()['url']);
    }

    public function testGetRedirectForInternalLinkWithQueryString()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('localization', null)->willReturn(new Localization('de'));

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request(
            [],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [], ['REQUEST_URI' => rawurlencode('/de/test'), 'QUERY_STRING' => 'test1=value1']
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/other-test?test1=value1', $route->getDefaults()['url']);
    }

    public function testGetRedirectForExternalLink()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('localization', null)->willReturn(new Localization('de'));

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/test');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('/test', 'webspace', 'de')->willReturn('some-uuid');

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::EXTERNAL);
        $document->getRedirectExternal()->willReturn('http://www.example.org');
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/de/test')]
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('http://www.example.org', $route->getDefaults()['url']);
    }

    public function testGetCollectionMovedResourceLocator()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $localization = new Localization('de', 'at');
        $attributes->getAttribute('localization', null)->willReturn($localization);
        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);

        $attributes->getAttribute('resourceLocator', null)->willReturn('/qwertz/');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->requestAnalyzer->getResourceLocator()->willReturn('/qwertz/');
        $this->requestAnalyzer->getResourceLocatorPrefix()->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')
            ->willThrow(new ResourceLocatorMovedException('/new-test', '123-123-123'));

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/de/qwertz/')]
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test', $route->getDefaults()['url']);
    }

    public function testGetCollectionForSingleLanguageRequestSlashOnly()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $localization = new Localization('de');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $pageBridge->getController()->willReturn('::Controller');
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $routes->getIterator()->current()->getDefaults()['structure']);
    }

    public function testGetCollectionTrailingSlash()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $localization = new Localization('de', 'at');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/qwertz/');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de-at');
        $attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $this->resourceLocatorStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])->willReturn(
            $document->reveal()
        );

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/de/qwertz/')]
        );
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/qwertz', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashWithQueryParams()
    {
        $attributes = $this->prophesize(RequestAttributes::class);
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);

        $localization = new Localization('de');
        $attributes->getAttribute('localization')->willReturn($localization);
        $attributes->getAttribute('matchType')->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator')->willReturn('/foo/');
        $attributes->getAttribute('resourceLocatorPrefix')->willReturn('/de');
        $attributes->getAttribute('portal')->willReturn($portal);

        $this->resourceLocatorStrategy->loadByResourceLocator('/foo', 'webspace', 'de')->willReturn('some-uuid');
        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/foo');
        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());
        $request = new Request(
            [],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [], ['REQUEST_URI' => rawurlencode('/de/foo/'), 'QUERY_STRING' => 'bar=baz']
        );
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/foo?bar=baz', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashForHomepage()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $localization = new Localization('de', 'at');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');
        $attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');
        $attributes->getAttribute('redirect', null)->willReturn('sulu.lo/de-at');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])->willReturn(
            $document->reveal()
        );

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/de/')]
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('SuluWebsiteBundle:Redirect:redirect', $route->getDefaults()['_controller']);
        $this->assertEquals('/de', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashForHomepageWithoutLocalization()
    {
        $attributes = $this->prophesize(RequestAttributes::class);

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $localization = new Localization('de', 'at');
        $attributes->getAttribute('localization', null)->willReturn($localization);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn('/');
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('');
        $attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $this->requestAnalyzer->getPortalUrl()->willReturn('sulu.lo');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);

        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])
            ->willReturn($document->reveal());

        $metadata = new Metadata();
        $metadata->setAlias('page');
        $structureMetadata = new StructureMetadata();
        $this->documentInspector->getMetadata($document->reveal())->willReturn($metadata);
        $this->documentInspector->getStructureMetadata($document->reveal())->willReturn($structureMetadata);

        $pageBridge = $this->prophesize(PageBridge::class);
        $this->structureManager->wrapStructure('page', $structureMetadata)->willReturn($pageBridge->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => rawurlencode('/')]
        );

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals($pageBridge->reveal(), $route->getDefaults()['structure']);
    }

    public function testGetCollectionForEmptyFormat()
    {
        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('');

        // Test the route provider
        $routes = $this->contentRouteProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $routes);
    }
}
