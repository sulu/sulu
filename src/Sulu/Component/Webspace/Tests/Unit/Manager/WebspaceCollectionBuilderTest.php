<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Prophecy\Argument;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;
use Sulu\Component\Webspace\Url\Replacer;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;

class WebspaceCollectionBuilderTest extends WebspaceTestCase
{
    /**
     * @var DelegatingLoader
     */
    private $loader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
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

        $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    }

    public function testBuild()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple'
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $webspaces = $webspaceCollection->getWebspaces();

        $this->assertCount(2, $webspaces);

        $this->assertEquals('Massive Art', $webspaces[0]->getName());
        $this->assertEquals('Sulu CMF', $webspaces[1]->getName());

        $this->assertEquals(2, count($webspaces[0]->getNavigation()->getContexts()));

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

        $this->assertCount(12, $prodPortalInformations);

        $prodPortalInformationKeys = array_keys($prodPortalInformations);
        $prodPortalInformationValues = array_values($prodPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[0]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[1]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[2]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[3]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[4]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[5]->getType());
        $this->assertEquals('www.sulu.at', $prodPortalInformationKeys[10]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, $prodPortalInformationValues[10]->getType());
        $this->assertEquals('sulu.at', $prodPortalInformationKeys[11]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[11]->getType());

        $devPortalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertCount(13, $devPortalInformations);

        $devPortalInformationValues = [];
        foreach ($devPortalInformations as $portalInformation) {
            $devPortalInformationValues[$portalInformation->getUrl()] = array_filter([
                'type' => $portalInformation->getType(),
                'redirect' => $portalInformation->getRedirect(),
                'locale' => $portalInformation->getLocale(),
            ]);
        }

        // massiveart-ca.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-ca.lo/en-ca/w']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-ca.lo/en-ca/s']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-ca.lo/fr-ca/w']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-ca.lo/fr-ca/s']
        );

        // massiveart-us.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-us.lo/en-ca/w']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-us.lo/en-ca/s']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_us'],
            $devPortalInformationValues['massiveart-us.lo/en-us/w']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_us'],
            $devPortalInformationValues['massiveart-us.lo/en-us/s']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-us.lo/fr-ca/w']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-us.lo/fr-ca/s']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-ca.lo/{localization}/s'],
            $devPortalInformationValues['massiveart-ca.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-us.lo/{localization}/s'],
            $devPortalInformationValues['massiveart-us.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'de_at'],
            $devPortalInformationValues['sulu.lo']
        );
    }

    public function testBuildWithMultipleLocalizationUrls()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple-localization-urls'
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();

        $portalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.de']->getType());
        $this->assertEquals('sulu.de', $portalInformations['sulu.de']->getUrl());
        $this->assertEquals('de', $portalInformations['sulu.de']->getLocalization()->getLocalization());

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.us']->getType());
        $this->assertEquals('sulu.us', $portalInformations['sulu.us']->getUrl());
        $this->assertEquals('en', $portalInformations['sulu.us']->getLocalization()->getLocalization());
    }

    public function testBuildWithMainUrl()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/main'
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

    public function testBuildWithCustomUrl()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            new Replacer(),
            $this->getResourceDirectory() . '/DataFixtures/Webspace/custom-url'
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
}
