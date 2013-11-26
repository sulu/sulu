<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Portal\Loader;

class XmlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    public function setUp()
    {
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', array('locate'));
        $locator->expects($this->once())->method('locate')->will($this->returnArgument(0));

        $this->loader = new XmlFileLoader($locator);
    }

    public function testLoad()
    {
        $workspace = $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Portal/valid/sulu.io.xml');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());
        $this->assertEquals(true, $workspace->getLocalizations()[0]->isDefault());

        $this->assertEquals('de', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());
        $this->assertEquals(false, $workspace->getLocalizations()[1]->isDefault());

        $this->assertEquals('short', $workspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(1, count($workspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('de', $workspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $workspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $workspace->getPortals()[0]->getLocalizations()[0]->isDefault());

        $this->assertEquals('sulu', $workspace->getPortals()[0]->getTheme()->getKey());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $workspace->getPortals()[0]->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $workspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $workspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $workspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->isMain());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadInvalid()
    {
        $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Portal/invalid/massiveart.xml');
    }
}
