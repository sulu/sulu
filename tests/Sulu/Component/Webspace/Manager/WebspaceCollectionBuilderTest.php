<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;


use Sulu\Component\Webspace\Loader\XmlFileLoader;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;

class WebspaceCollectionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceCollectionBuilder
     */
    private $webspaceCollectionBuilder;

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
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', array('locate'));
        $locator->expects($this->any())->method('locate')->will($this->returnArgument(0));
        $this->loader = new XmlFileLoader($locator);

        $this->logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();

        $this->webspaceCollectionBuilder = new WebspaceCollectionBuilder(
            $this->loader,
            $this->logger,
            __DIR__ . '/../../../../Resources/DataFixtures/Webspace/both'
        );
    }

    public function testBuild()
    {
        $this->logger->expects($this->once())->method('warning');

        $webspaceCollection = $this->webspaceCollectionBuilder->build();

        $webspaces = $webspaceCollection->getWebspaces();

        $this->assertCount(2, $webspaces);

        $this->assertEquals('Massive Art', $webspaces[0]->getName());
        $this->assertEquals('Sulu CMF', $webspaces[1]->getName());

        $portals = $webspaceCollection->getPortals();

        $this->assertCount(3, $portals);

        $this->assertEquals('Massive Art US', $portals[0]->getName());
        $this->assertEquals('Massive Art CA', $portals[1]->getName());
        $this->assertEquals('Sulu CMF AT', $portals[2]->getName());

        $prodPortalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertCount(8, $prodPortalInformations);

        $prodPortalInformationKeys = array_keys($prodPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals('www.sulu.at', $prodPortalInformationKeys[6]);
        $this->assertEquals('sulu.at', $prodPortalInformationKeys[7]);

        $devPortalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertCount(7, $devPortalInformations);

        $devPortalInformationKeys = array_keys($devPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals('sulu.lo', $devPortalInformationKeys[6]);
    }
}
