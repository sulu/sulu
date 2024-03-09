<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Manager;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class WebspaceManagerTest extends WebspaceTestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var WebspaceManager
     */
    protected $webspaceManager;

    /**
     * @var ObjectProphecy<StructureMetadataFactoryInterface>
     */
    private $structureMetadataFactory;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->structureMetadataFactory = $this->prophesize(StructureMetadataFactoryInterface::class);

        $defaultStructure = new StructureMetadata('default');
        $overviewStructure = new StructureMetadata('overview');
        $this->structureMetadataFactory
            ->getStructures('page')
            ->willReturn([$defaultStructure, $overviewStructure]);

        // todo: Replace this with proper mocks
        $webspaceCollection = new WebspaceCollectionCache();

        $this->webspaceManager = new WebspaceManager(
            $webspaceCollection,
            new Replacer(),
            $this->requestStack->reveal(),
            'test',
            'sulu.io',
            'http',
            $this->structureMetadataFactory->reveal()
        );
    }

    public function testFindPortalInformationByUrl(): void
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.at/test/test/test', 'prod');
        $this->assertNotNull($portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());
    }

    public function testFindPortalInformationsByUrl(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl('sulu.at/test/test/test', 'prod');
        $this->assertNotCount(0, $portalInformations);
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocale());
    }

    /**
     * @return array<array{string, bool}>
     */
    public function provideFindPortalInformationByUrl(): array
    {
        return [
            ['dan.lo/de-asd/test/test', false],
            ['dan.lo/de-asd/test/test.rss', false],
            ['dan.lo/de/test/test', true],
            ['dan.lo/de/test/test.rss', true],
            ['dan.lo/de-asd', false],
            ['dan.lo/de-asd.rss', false],
            ['dan.lo/de/s', true],
            ['dan.lo/de/s.rss', true],
            ['dan.lo/de', true],
            ['dan.lo/de.rss', true],
        ];
    }

    /**
     * @dataProvider provideFindPortalInformationByUrl
     *
     * @param bool $shouldFind
     */
    public function testFindPortalInformationByUrlWithInvalidSuffix(string $url, $shouldFind): void
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl($url, 'dev');

        if ($shouldFind) {
            $this->assertNotNull($portalInformation);
        } else {
            $this->assertNull($portalInformation);
        }
    }

    public function testFindPortalInformationsByWebspaceKeyAndLocale(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            'sulu_io',
            'de_at',
            'dev'
        );

        $this->assertCount(1, $portalInformations);
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);

        $this->assertEquals('sulu_io', $portalInformation->getWebspace()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testFindPortalInformationsByPortalKeyAndLocale(): void
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByPortalKeyAndLocale(
            'sulucmf_at',
            'de_at',
            'dev'
        );
        $portalInformation = \reset($portalInformations);
        $this->assertInstanceOf(PortalInformation::class, $portalInformation);

        $this->assertEquals('sulucmf_at', $portalInformation->getPortal()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testFindUrlsByResourceLocator(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithScheme(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator(
            '/test',
            'dev',
            'en_us',
            'massiveart',
            null,
            'https'
        );

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithSchemeNull(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator(
            '/test',
            'dev',
            'en_us',
            'massiveart',
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, null);
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithSchemeFromRequest(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $request->getScheme()->willReturn('https');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['https://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithWebspaceFromRequest(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('dan_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at');

        $this->assertEquals(['http://dan.lo/de/test'], $result);
    }

    public function testFindUrlsByResourceLocatorRoot(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'en_us', 'massiveart');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo/en-us', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['http://sulu.lo/'], $result);
    }

    public function testFindUrlsByResourceLocatorRootWithScheme(): void
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'en_us', 'massiveart', null, 'https');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo/'], $result);
    }

    public function testFindUrlsByResourceLocatorWithCustomHttpPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'http');

        $this->assertCount(1, $result);
        $this->assertContains('http://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'http');
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithCustomHttpsPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.lo');
        $request->getPort()->willReturn(4444);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'https');

        $this->assertCount(1, $result);
        $this->assertContains('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo:4444/test'], $result);
    }

    public function testFindUrlByResourceLocator(): void
    {
        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.lo/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.at/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io', 'sulu.lo');
        $this->assertEquals('http://sulu.lo/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io', 'other-domain.lo');
        $this->assertEquals('http://sulu.at/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            'main',
            'de_at',
            'sulu_io',
            'sulu.lo',
            'https'
        );
        $this->assertEquals('https://sulu.lo/test', $result);
    }

    public function testFindUrlByResourceLocatorWithWebspaceFromRequest(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('dan_io');
        $request = new Request([], [], ['_sulu' => new RequestAttributes(['webspace' => $webspace])]);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at');
        $this->assertEquals('http://dan.lo/de/test', $result);
    }

    public function testFindUrlByResourceLocatorWithCustomHttpPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('massiveart.lo');
        $request->getPort()->willReturn(8080);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'http');
        $this->assertEquals('http://massiveart.lo:8080/en-us/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'http');
        $this->assertEquals('http://sulu.lo/test', $result);
    }

    public function testFindUrlByResourceLocatorWithCustomHttpsPort(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.lo');
        $request->getPort()->willReturn(4444);
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'en_us', 'massiveart', null, 'https');
        $this->assertEquals('https://massiveart.lo/en-us/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals('https://sulu.lo:4444/test', $result);
    }

    public function testGetUrls(): void
    {
        $urls = $this->webspaceManager->getUrls('dev');

        $this->assertCount(9, $urls);
        $this->assertContains('sulu.lo', $urls);
        $this->assertContains('sulu-single-language.lo', $urls);
        $this->assertContains('sulu-without.lo', $urls);
        $this->assertContains('massiveart.lo', $urls);
        $this->assertContains('massiveart.lo/en-us', $urls);
        $this->assertContains('massiveart.lo/en-ca', $urls);
        $this->assertContains('massiveart.lo/fr-ca', $urls);
        $this->assertContains('massiveart.lo/de', $urls);
    }

    public function testGetAllLocalizations(): void
    {
        $localizations = $this->webspaceManager->getAllLocalizations();

        $localizations = \array_map(
            function($localization) {
                $localization = $localization->toArray();
                unset($localization['children']);
                unset($localization['localization']);
                unset($localization['shadow']);
                unset($localization['default']);

                return $localization;
            },
            $localizations
        );

        // check for duplicates
        $this->assertCount(7, $localizations);

        $this->assertContains(['country' => 'us', 'language' => 'en'], $localizations);
        $this->assertContains(['country' => 'at', 'language' => 'de'], $localizations);
        $this->assertContains(['country' => 'ca', 'language' => 'en'], $localizations);
        $this->assertContains(['country' => 'ca', 'language' => 'fr'], $localizations);

        $this->assertContains(['country' => '', 'language' => 'de'], $localizations);
        $this->assertContains(['country' => '', 'language' => 'en'], $localizations);
        $this->assertContains(['country' => 'uk', 'language' => 'en'], $localizations);
    }

    public function testGetAllLocalesByWebspaces(): void
    {
        $webspacesLocales = $this->webspaceManager->getAllLocalesByWebspaces();

        foreach ($webspacesLocales as &$webspaceLocales) {
            $webspaceLocales = \array_map(
                function($webspaceLocale) {
                    $webspaceLocale = $webspaceLocale->toArray();
                    unset($webspaceLocale['children']);
                    unset($webspaceLocale['localization']);
                    unset($webspaceLocale['shadow']);
                    unset($webspaceLocale['default']);

                    return $webspaceLocale;
                },
                $webspaceLocales
            );
        }

        $this->assertArrayHasKey('sulu_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['sulu_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['sulu_io']);
        $this->assertEquals(['country' => 'us', 'language' => 'en'], $webspacesLocales['sulu_io']['en_us']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], \reset($webspacesLocales['sulu_io']));

        $this->assertArrayHasKey('massiveart', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('en_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('fr_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('de', $webspacesLocales['massiveart']);
        $this->assertEquals(['country' => 'ca', 'language' => 'fr'], \reset($webspacesLocales['massiveart']));

        $this->assertArrayHasKey('dan_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['dan_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['dan_io']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], \reset($webspacesLocales['dan_io']));
    }

    public function testGetWebspaceCollectionReplaceHost(): void
    {
        $portalInformations = \array_values(
            $this->webspaceManager->getPortalInformationsByWebspaceKey(
                'test',
                'sulu_io_host_replacement'
            )
        );

        $this->assertCount(2, $portalInformations);

        $this->assertEquals('sulu.io/de-at', $portalInformations[0]->getUrl());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[0]->getUrlExpression());
        $this->assertEquals('sulu.io', $portalInformations[1]->getUrl());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[1]->getUrlExpression());
        $this->assertEquals('sulu.io/{localization}', $portalInformations[1]->getRedirect());
    }
}
