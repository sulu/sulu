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

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Loader\XmlFileLoader;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;

class WebspaceCollectionBuilderTest extends WebspaceTestCase
{
    /**
     * @var XmlFileLoader
     */
    private $loader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', ['locate']);
        $locator->expects($this->any())->method('locate')->will($this->returnArgument(0));
        $this->loader = new XmlFileLoader($locator);

        $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();
    }

    public function testBuild()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            $this->logger,
            $this->getResourceDirectory() . '/DataFixtures/Webspace/both'
        );

        $this->logger->expects($this->once())->method('warning');

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

        $this->assertCount(8, $prodPortalInformations);

        $prodPortalInformationKeys = array_keys($prodPortalInformations);
        $prodPortalInformationValues = array_values($prodPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[0]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[1]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[2]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[3]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[4]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[5]->getType());
        $this->assertEquals('www.sulu.at', $prodPortalInformationKeys[6]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, $prodPortalInformationValues[6]->getType());
        $this->assertEquals('sulu.at', $prodPortalInformationKeys[7]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[7]->getType());

        $devPortalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertCount(9, $devPortalInformations);

        $devPortalInformationKeys = array_keys($devPortalInformations);
        /** @var PortalInformation[] $devPortalInformationValues */
        $devPortalInformationValues = array_values($devPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[0]->getType());
        $this->assertNull($devPortalInformationValues[0]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[1]->getType());
        $this->assertNull($devPortalInformationValues[1]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[2]->getType());
        $this->assertNull($devPortalInformationValues[2]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[3]->getType());
        $this->assertNull($devPortalInformationValues[3]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[4]->getType());
        $this->assertNull($devPortalInformationValues[4]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[5]->getType());
        $this->assertNull($devPortalInformationValues[5]->getRedirect());
        $this->assertEquals('massiveart-us.lo', $devPortalInformationKeys[6]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, $devPortalInformationValues[6]->getType());
        $this->assertEquals('massiveart-us.lo/en-us/s', $devPortalInformationValues[6]->getRedirect());
        $this->assertEquals('massiveart-ca.lo', $devPortalInformationKeys[7]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, $devPortalInformationValues[7]->getType());
        $this->assertEquals('massiveart-ca.lo/fr-ca/s', $devPortalInformationValues[7]->getRedirect());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, $devPortalInformationValues[7]->getType());
        $this->assertEquals('sulu.lo', $devPortalInformationKeys[8]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $devPortalInformationValues[8]->getType());

        $this->assertEquals('en_us', $devPortalInformationValues[6]->getLocalization()->getLocalization());
        $this->assertEquals('s', $devPortalInformationValues[6]->getSegment()->getKey());
        $this->assertEquals('fr_ca', $devPortalInformationValues[7]->getLocalization()->getLocalization());
        $this->assertEquals('s', $devPortalInformationValues[7]->getSegment()->getKey());
    }

    public function testBuildWithMultipleLocalizationUrls()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            $this->logger,
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

    /**
     * @expectedException \Sulu\Component\Webspace\Exception\NoValidWebspaceException
     */
    public function testBuildWithInvalidWebspacesOnly()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            $this->logger,
            $this->getResourceDirectory() . '/DataFixtures/Webspace/not-valid'
        );

        $webspaceCollection = $webspaceCollectionBuilder->build();
    }

    public function testBuildWithMainUrl()
    {
        $webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            $this->logger,
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
}
