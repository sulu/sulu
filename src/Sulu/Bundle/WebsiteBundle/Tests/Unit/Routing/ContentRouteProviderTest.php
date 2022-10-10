<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\WebsiteBundle\Routing\ContentRouteProvider;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\Behavior\ExtensionBehavior;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Document\Behavior\StructureBehavior;
use Sulu\Component\Content\Document\Behavior\WebspaceBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\DocumentManager\Behavior\Mapping\TitleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\Document\UnknownDocument;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Metadata;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzer;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Security;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

class ContentRouteProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentManagerInterface>
     */
    private $documentManager;

    /**
     * @var ObjectProphecy<DocumentInspector>
     */
    private $documentInspector;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyInterface>
     */
    private $resourceLocatorStrategy;

    /**
     * @var ObjectProphecy<ResourceLocatorStrategyPoolInterface>
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var ObjectProphecy<StructureManagerInterface>
     */
    private $structureManager;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private $webspaceManager;

    public function setUp(): void
    {
        $this->documentManager = $this->prophesize(DocumentManagerInterface::class);
        $this->documentInspector = $this->prophesize(DocumentInspector::class);
        $this->resourceLocatorStrategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);
        $this->structureManager = $this->prophesize(StructureManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey(Argument::any())->willReturn($this->resourceLocatorStrategy->reveal());
    }

    public function testStateTest(): void
    {
        $localization = new Localization();
        $localization->setLanguage('de');

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => \rawurlencode('/')]);

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequest(): void
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
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['other-webspace' => null]]]);
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
            ['REQUEST_URI' => \rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testSecurityChecker(): void
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
        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(true);
        $webspace->setSecurity($security);
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn(null);
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(WebspaceBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getUuid()->willReturn('some-uuid');
        $document->getTitle()->willReturn('some-title');
        $document->getWebspaceName()->willReturn('webspace');
        $document->getLocale()->willReturn('de');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => null]]);
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
            ['REQUEST_URI' => \rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->checkPermission(Argument::that(function(SecurityCondition $securityCondition) use ($document) {
            $this->assertSame('some-uuid', $securityCondition->getObjectId());
            $this->assertSame(\get_class($document->reveal()), $securityCondition->getObjectType());
            $this->assertSame('de', $securityCondition->getLocale());
            $this->assertSame('sulu.webspaces.webspace', $securityCondition->getSecurityContext());

            return true;
        }), PermissionTypes::VIEW)->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider($securityChecker->reveal());
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testSecurityCheckerWithoutPermissionCheck(): void
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
        $security = new Security();
        $security->setSystem('website');
        $security->setPermissionCheck(false);
        $webspace->setSecurity($security);
        $portal->setWebspace($webspace);
        $attributes->getAttribute('portal', null)->willReturn($portal);

        $attributes->getAttribute('matchType', null)->willReturn(RequestAnalyzer::MATCH_TYPE_FULL);
        $attributes->getAttribute('resourceLocator', null)->willReturn(null);
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn('/de');

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(WebspaceBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getUuid()->willReturn('some-uuid');
        $document->getTitle()->willReturn('some-title');
        $document->getWebspaceName()->willReturn('webspace');
        $document->getLocale()->willReturn('de');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => null]]);
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
            ['REQUEST_URI' => \rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $securityChecker = $this->prophesize(SecurityCheckerInterface::class);
        $securityChecker->checkPermission(Argument::cetera())->shouldNotBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider($securityChecker->reveal());
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
    }

    public function testGetCollectionForRequestWithWrongSegment(): void
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
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['webspace' => 'w']]]);
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $segment = new Segment();
        $segment->setKey('s');
        $this->requestAnalyzer->getSegment()->willReturn($segment);

        $this->requestAnalyzer->changeSegment('w')->shouldBeCalled();

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
            ['REQUEST_URI' => \rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider();
        $contentRouteProvider->getRouteCollectionForRequest($request);
    }

    public function testGetCollectionForRequestWithUmlauts(): void
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

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['webspace' => null]]]);
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
            ['REQUEST_URI' => \rawurlencode('/de/käße')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        /** @var Route $route */
        $route = $routes->getIterator()->current();
        $defaults = $route->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(false, $defaults['partial']);
        $this->assertEquals('/de/käße', $route->getPath());
    }

    public function testGetCollectionForRequestWithMissingStructure(): void
    {
        $localization = new Localization();
        $localization->setLanguage('de');

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);

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

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => \rawurlencode('/')]);

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForRequestWithPartialFlag(): void
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
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['webspace' => null]]]);
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
            ['REQUEST_URI' => \rawurlencode('/de')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $defaults = $routes->getIterator()->current()->getDefaults();

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $defaults['structure']);
        $this->assertEquals(true, $defaults['partial']);
    }

    public function testGetCollectionForRequestNoLocalization(): void
    {
        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => \rawurlencode('/')]);

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetCollectionForNotExistingRequest(): void
    {
        $localization = new Localization();
        $localization->setLanguage('de');

        $portal = new Portal();
        $portal->setKey('portal');
        $webspace = new Webspace();
        $webspace->setKey('webspace');
        $webspace->setTheme('theme');
        $portal->setWebspace($webspace);

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de')
            ->willThrow(ResourceLocatorNotFoundException::class);

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => \rawurlencode('/')]);
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetRedirectForInternalLink(): void
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

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(WebspaceBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');
        $redirectTargetDocument->getWebspaceName()->willReturn('sulu');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getLocale()->willReturn('de');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/test')]
        );

        $this->webspaceManager->findUrlByResourceLocator('/other-test', null, 'de', 'sulu')
             ->shouldBeCalled()
             ->willReturn('sulu.io/de/other-test');

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.io/de/other-test', $route->getDefaults()['url']);
    }

    public function testGetRedirectForInternalLinkWithUnpublishedTarget(): void
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

        $redirectTargetDocument = $this->prophesize(UnknownDocument::class);

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getLocale()->willReturn('de');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/test')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(0, $routes);
    }

    public function testGetRedirectForInternalLinkWithQueryString(): void
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

        $redirectTargetDocument = $this->prophesize(ResourceSegmentBehavior::class)
            ->willImplement(WebspaceBehavior::class);
        $redirectTargetDocument->getResourceSegment()->willReturn('/other-test');
        $redirectTargetDocument->getWebspaceName()->willReturn('sulu');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::INTERNAL);
        $document->getRedirectTarget()->willReturn($redirectTargetDocument->reveal());
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getLocale()->willReturn('de');
        $this->documentManager->find('some-uuid', 'de', ['load_ghost_content' => false])->willReturn($document->reveal());

        $this->webspaceManager->findUrlByResourceLocator('/other-test', null, 'de', 'sulu')
             ->shouldBeCalled()
             ->willReturn('sulu.io/de/other-test');

        $request = new Request(
            [],
            [],
            ['_sulu' => $attributes->reveal()],
            [],
            [], ['REQUEST_URI' => \rawurlencode('/de/test'), 'QUERY_STRING' => 'test1=value1']
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('sulu.io/de/other-test?test1=value1', $route->getDefaults()['url']);
    }

    public function testGetRedirectForInternalLinkWithJsonFormat(): void
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
            [], [], ['_sulu' => $attributes->reveal(), '_format' => 'json'], [], [], ['REQUEST_URI' => \rawurlencode('/de/qwertz/')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test.json', $route->getDefaults()['url']);
    }

    public function testGetRedirectForExternalLink(): void
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
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/test')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('http://www.example.org', $route->getDefaults()['url']);
    }

    public function testGetCollectionMovedResourceLocator(): void
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

        $this->resourceLocatorStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')
            ->willThrow(new ResourceLocatorMovedException('/new-test', '123-123-123'));

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/qwertz/')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/new-test', $route->getDefaults()['url']);
    }

    public function testGetCollectionForSingleLanguageRequestSlashOnly(): void
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
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['webspace' => null]]]);
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
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/')]
        );

        $pageBridge->setDocument($document->reveal())->shouldBeCalled();

        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $this->assertEquals($pageBridge->reveal(), $routes->getIterator()->current()->getDefaults()['structure']);
    }

    public function testGetCollectionTrailingSlash(): void
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
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/qwertz/')]
        );
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/qwertz', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashWithQueryParams(): void
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
            [], ['REQUEST_URI' => \rawurlencode('/de/foo/'), 'QUERY_STRING' => 'bar=baz']
        );
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);
        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/de/foo?bar=baz', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashWithoutPrefix(): void
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
        $attributes->getAttribute('resourceLocatorPrefix', null)->willReturn(null);
        $attributes->getAttribute('redirect', null)->willReturn('sulu.lo/qwertz');
        $attributes->getAttribute('portalUrl', null)->willReturn('sulu.lo');

        $this->resourceLocatorStrategy->loadByResourceLocator('/qwertz', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class);
        $document->getTitle()->willReturn('some-title');
        $this->documentManager->find('some-uuid', 'de_at', ['load_ghost_content' => false])->willReturn(
            $document->reveal()
        );

        $request = new Request(
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/qwertz/')]
        );
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/qwertz', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashForHomepage(): void
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
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/de/')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals('sulu_website.redirect_controller::redirectAction', $route->getDefaults()['_controller']);
        $this->assertEquals('/de', $route->getDefaults()['url']);
    }

    public function testGetCollectionTrailingSlashForHomepageWithoutLocalization(): void
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

        $this->resourceLocatorStrategy->loadByResourceLocator('', 'webspace', 'de_at')->willReturn('some-uuid');

        $document = $this->prophesize(TitleBehavior::class)
            ->willImplement(ExtensionBehavior::class)
            ->willImplement(RedirectTypeBehavior::class)
            ->willImplement(StructureBehavior::class)
            ->willImplement(UuidBehavior::class);

        $document->getTitle()->willReturn('some-title');
        $document->getRedirectType()->willReturn(RedirectType::NONE);
        $document->getStructureType()->willReturn('default');
        $document->getUuid()->willReturn('some-uuid');
        $document->getExtensionsData()->willReturn(['excerpt' => ['segments' => ['other-webspace' => null]]]);
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
            [], [], ['_sulu' => $attributes->reveal()], [], [], ['REQUEST_URI' => \rawurlencode('/')]
        );

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $routes);
        $route = $routes->getIterator()->current();
        $this->assertEquals($pageBridge->reveal(), $route->getDefaults()['structure']);
    }

    public function testGetCollectionForEmptyFormat(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getRequestFormat()->willReturn('');

        // Test the route provider
        $contentRouteProvider = $this->createContentRouteProvider();
        $routes = $contentRouteProvider->getRouteCollectionForRequest($request->reveal());

        $this->assertCount(0, $routes);
    }

    private function createContentRouteProvider(SecurityCheckerInterface $securityChecker = null): ContentRouteProvider
    {
        return new ContentRouteProvider(
            $this->documentManager->reveal(),
            $this->documentInspector->reveal(),
            $this->resourceLocatorStrategyPool->reveal(),
            $this->structureManager->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $securityChecker
        );
    }
}
