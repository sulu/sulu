<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Exception\InvalidTemplateException;
use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;
use Sulu\Component\Webspace\Url\Replacer;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

class WebspaceCollectionBuilderTest extends WebspaceTestCase
{
    use ProphecyTrait;

    /**
     * @var DelegatingLoader
     */
    private $loader;

    /**
     * @var \PHPUnit\Framework\MockObject_MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(function($arguments) {
            return $arguments[0];
        });

        $resolver = new LoaderResolver([
            new XmlFileLoader11($locator->reveal()),
            new XmlFileLoader10($locator->reveal()),
        ]);

        $this->loader = new DelegatingLoader($resolver);

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
    }

    public function testBuild(): void
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $webspaces = $webspaceCollection->getWebspaces();

        $this->assertCount(2, $webspaces);

        $this->assertEquals('Massive Art', $webspaces[0]->getName());
        $this->assertEquals('Sulu CMF', $webspaces[1]->getName());

        $this->assertEquals(2, \count($webspaces[0]->getNavigation()->getContexts()));

        $this->assertEquals('main', $webspaces[0]->getNavigation()->getContexts()[0]->getKey());
        $this->assertEquals('Hauptnavigation', $webspaces[0]->getNavigation()->getContexts()[0]->getTitle('de'));
        $this->assertEquals('Mainnavigation', $webspaces[0]->getNavigation()->getContexts()[0]->getTitle('en'));
        $this->assertEquals('Main', $webspaces[0]->getNavigation()->getContexts()[0]->getTitle('fr'));

        $this->assertEquals('footer', $webspaces[0]->getNavigation()->getContexts()[1]->getKey());
        $this->assertEquals('Unten', $webspaces[0]->getNavigation()->getContexts()[1]->getTitle('de'));
        $this->assertEquals('Footer', $webspaces[0]->getNavigation()->getContexts()[1]->getTitle('en'));
        $this->assertEquals('Footer', $webspaces[0]->getNavigation()->getContexts()[1]->getTitle('fr'));

        $portals = $webspaceCollection->getPortals();

        $this->assertCount(3, $portals);

        $this->assertEquals('Massive Art US', $portals[0]->getName());
        $this->assertEquals('Massive Art CA', $portals[1]->getName());
        $this->assertEquals('Sulu CMF AT', $portals[2]->getName());

        $prodPortalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertCount(7, $prodPortalInformations);

        $prodPortalInformationKeys = \array_keys($prodPortalInformations);
        $prodPortalInformationValues = \array_values($prodPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[0]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[1]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[2]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[3]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[4]->getType());
        $this->assertEquals('www.sulu.at', $prodPortalInformationKeys[5]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, $prodPortalInformationValues[5]->getType());
        $this->assertEquals('sulu.at', $prodPortalInformationKeys[6]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[6]->getType());

        $devPortalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertCount(8, $devPortalInformations);

        $devPortalInformationValues = [];
        foreach ($devPortalInformations as $portalInformation) {
            $devPortalInformationValues[$portalInformation->getUrl()] = \array_filter([
                'type' => $portalInformation->getType(),
                'redirect' => $portalInformation->getRedirect(),
                'locale' => $portalInformation->getLocale(),
            ]);
        }

        // massiveart-ca.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-ca.lo/en-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-ca.lo/fr-ca']
        );

        // massiveart-us.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-us.lo/en-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_us'],
            $devPortalInformationValues['massiveart-us.lo/en-us']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-us.lo/fr-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-ca.lo/{localization}'],
            $devPortalInformationValues['massiveart-ca.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-us.lo/{localization}'],
            $devPortalInformationValues['massiveart-us.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'de_at'],
            $devPortalInformationValues['sulu.lo']
        );
    }

    public function testBuildWithMultipleLocalizationUrls(): void
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple-localization-urls',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $portalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.de']->getType());
        $this->assertEquals('sulu.de', $portalInformations['sulu.de']->getUrl());
        $this->assertEquals('de', $portalInformations['sulu.de']->getLocalization()->getLocale());

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.us']->getType());
        $this->assertEquals('sulu.us', $portalInformations['sulu.us']->getUrl());
        $this->assertEquals('en', $portalInformations['sulu.us']->getLocalization()->getLocale());
    }

    public function testBuildWithMainUrl(): void
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/main',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $webspace = $webspaceCollection->getWebspaces()[0];
        $this->assertEquals('sulu_io', $webspace->getKey());

        $dev = $webspace->getPortals()[0]->getEnvironment('dev');
        $prod = $webspace->getPortals()[0]->getEnvironment('prod');
        $main = $webspace->getPortals()[0]->getEnvironment('main');

        $this->assertCount(1, $dev->getUrls());
        $this->assertCount(2, $prod->getUrls());
        $this->assertCount(3, $main->getUrls());

        $this->assertEquals('sulu.lo', $dev->getMainUrl()->getUrl());
        $this->assertEquals('www.sulu.at', $prod->getMainUrl()->getUrl());
        $this->assertEquals('sulu.at', $main->getMainUrl()->getUrl());
    }

    public function testBuildWithCustomUrl(): void
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/custom-url',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $webspace = $webspaceCollection->getWebspaces()[0];
        $this->assertEquals('sulu_io', $webspace->getKey());

        $dev = $webspace->getPortals()[0]->getEnvironment('dev');
        $prod = $webspace->getPortals()[0]->getEnvironment('prod');
        $stage = $webspace->getPortals()[0]->getEnvironment('stage');

        $this->assertCount(1, $dev->getCustomUrls());
        $this->assertCount(1, $stage->getCustomUrls());
        $this->assertCount(2, $prod->getCustomUrls());

        $this->assertEquals('dev.sulu.lo/*', $dev->getCustomUrls()[0]->getUrl());
        $this->assertEquals('stage.sulu.lo/*', $stage->getCustomUrls()[0]->getUrl());
        $this->assertEquals('sulu.lo/*', $prod->getCustomUrls()[0]->getUrl());
        $this->assertEquals('*.sulu.lo', $prod->getCustomUrls()[1]->getUrl());
    }

    public function testLanguageSpecificPartial(): void
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/language-specific',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $portalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertSame([
            'austria.sulu.io/de',
            'austria.sulu.io',
            'usa.sulu.io/en',
            'usa.sulu.io',
        ], \array_keys($portalInformations));

        $this->assertSame($portalInformations['austria.sulu.io/de']->getPriority(), 10);
        $this->assertSame($portalInformations['austria.sulu.io']->getPriority(), 9);
        $this->assertSame($portalInformations['usa.sulu.io/en']->getPriority(), 10);
        $this->assertSame($portalInformations['usa.sulu.io']->getPriority(), 9);
    }

    public function testThrowForMissingDefaultTemplate(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/missing-default-template',
            []
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();
    }

    public function testThrowForMissingExcludedTemplate(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/excluded-default-template',
            ['default', 'overview']
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();
    }
}
