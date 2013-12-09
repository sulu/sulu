<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Loader;

class XmlFileLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    public function setUp()
    {
        $locator = $this->getMock('\Symfony\Component\Config\FileLocatorInterface', array('locate'));
        $locator->expects($this->any())->method('locate')->will($this->returnArgument(0));

        $this->loader = new XmlFileLoader($locator);
    }

    public function testLoad()
    {
        $workspace = $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Workspace/valid/sulu.io.xml');

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io', $workspace->getKey());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());

        $this->assertEquals('de', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('short', $workspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(1, count($workspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('de', $workspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $workspace->getPortals()[0]->getLocalizations()[0]->getCountry());

        $this->assertEquals('sulu', $workspace->getPortals()[0]->getTheme()->getKey());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $workspace->getPortals()[0]->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $workspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('de', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals('at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getRedirect());
        $this->assertEquals('www.sulu.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(null, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals(null, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('sulu.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $workspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $workspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl());

        $workspace = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Workspace/valid/massiveart.xml'
        );

        $this->assertEquals('Massive Art', $workspace->getName());
        $this->assertEquals('massiveart', $workspace->getKey());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());

        $this->assertEquals(1, count($workspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $workspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('fr', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('de', $workspace->getLocalizations()[2]->getLanguage());
        $this->assertEquals(null, $workspace->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[2]->getShadow());

        $this->assertEquals('w', $workspace->getSegments()[0]->getKey());
        $this->assertEquals('winter', $workspace->getSegments()[0]->getName());
        $this->assertEquals('s', $workspace->getSegments()[1]->getKey());
        $this->assertEquals('summer', $workspace->getSegments()[1]->getName());

        $this->assertEquals('tree', $workspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(2, count($workspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $workspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('de', $workspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $workspace->getPortals()[0]->getLocalizations()[1]->getCountry());

        $this->assertEquals('Massive Art US', $workspace->getPortals()[0]->getName());
        $this->assertEquals('massiveart', $workspace->getPortals()[0]->getTheme()->getKey());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $workspace->getPortals()[0]->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $workspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals(
            '{language}.massiveart.{country}/{segment}',
            $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl()
        );

        $this->assertEquals('dev', $workspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals(
            'massiveart.lo/{localization}/{segment}',
            $workspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl()
        );

        $this->assertEquals('Massive Art CA', $workspace->getPortals()[1]->getName());
        $this->assertEquals('tree', $workspace->getPortals()[1]->getResourceLocatorStrategy());

        $this->assertEquals(2, count($workspace->getPortals()[1]->getLocalizations()));
        $this->assertEquals('en', $workspace->getPortals()[1]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $workspace->getPortals()[1]->getLocalizations()[0]->getCountry());
        $this->assertEquals('fr', $workspace->getPortals()[1]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $workspace->getPortals()[1]->getLocalizations()[1]->getCountry());

        $this->assertEquals('massiveart', $workspace->getPortals()[1]->getTheme()->getKey());
        $this->assertEquals(1, count($workspace->getPortals()[1]->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $workspace->getPortals()[1]->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($workspace->getPortals()[1]->getEnvironments()));

        $this->assertEquals('prod', $workspace->getPortals()[1]->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($workspace->getPortals()[1]->getEnvironments()[0]->getUrls()));
        $this->assertEquals(
            '{language}.massiveart.{country}/{segment}',
            $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getUrl()
        );
        $this->assertEquals(null, $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals(null, $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getRedirect());

        $this->assertEquals('www.massiveart.com', $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals('ca', $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('en', $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals('s', $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $workspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $workspace->getPortals()[1]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[1]->getEnvironments()[1]->getUrls()));
        $this->assertEquals(
            'massiveart.lo/{localization}/{segment}',
            $workspace->getPortals()[1]->getEnvironments()[1]->getUrls()[0]->getUrl()
        );
    }

    public function testLoadWithoutPortalLocalizations()
    {
        $workspace = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Workspace/valid/sulu.io_withoutPortalLocalization.xml'
        );

        $this->assertEquals('Sulu CMF', $workspace->getName());
        $this->assertEquals('sulu_io_without_portal_localization', $workspace->getKey());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getLocalizations()[0]->getShadow());

        $this->assertEquals('en', $workspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('uk', $workspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('de', $workspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $workspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('short', $workspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(3, count($workspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $workspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $workspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $workspace->getPortals()[0]->getLocalizations()[0]->getShadow());
        $this->assertEquals('en', $workspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('uk', $workspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $workspace->getPortals()[0]->getLocalizations()[1]->getShadow());
        $this->assertEquals('de', $workspace->getPortals()[0]->getLocalizations()[2]->getLanguage());
        $this->assertEquals('at', $workspace->getPortals()[0]->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $workspace->getPortals()[0]->getLocalizations()[2]->getShadow());

        $this->assertEquals('sulu', $workspace->getPortals()[0]->getTheme()->getKey());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $workspace->getPortals()[0]->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($workspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $workspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu-without.at', $workspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl());

        $this->assertEquals('dev', $workspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($workspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu-without.lo', $workspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl());
    }

    /**
     * @expectedException \Sulu\Component\Workspace\Loader\Exception\InvalidUrlDefinitionException
     */
    public function testLoadWithIncorrectUrlDefinition()
    {
        $workspace = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Workspace/invalid/massiveart_withIncorrectUrls.xml'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadInvalid()
    {
        $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Workspace/invalid/massiveart.xml');
    }
}
