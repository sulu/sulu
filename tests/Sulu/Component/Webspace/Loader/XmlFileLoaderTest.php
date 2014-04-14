<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Loader;

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
        $webspace = $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Webspace/valid/sulu.io.xml');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());

        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('sulu', $webspace->getTheme()->getKey());
        $this->assertEquals(1, count($webspace->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $webspace->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals('short', $webspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(1, count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('at', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());

        $this->assertEquals(2, count($webspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $webspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($webspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.at', $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals('de', $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals('at', $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getRedirect());
        $this->assertEquals('www.sulu.at', $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(null, $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals(null, $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('sulu.at', $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $webspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $webspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl());

        $webspace = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Webspace/valid/massiveart.xml'
        );

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());

        $this->assertEquals(1, count($webspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('fr', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('de', $webspace->getLocalizations()[2]->getLanguage());
        $this->assertEquals(null, $webspace->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[2]->getShadow());

        $this->assertEquals('w', $webspace->getSegments()[0]->getKey());
        $this->assertEquals('winter', $webspace->getSegments()[0]->getName());
        $this->assertEquals('s', $webspace->getSegments()[1]->getKey());
        $this->assertEquals('summer', $webspace->getSegments()[1]->getName());

        $this->assertEquals('massiveart', $webspace->getTheme()->getKey());
        $this->assertEquals(1, count($webspace->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $webspace->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals('tree', $webspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(2, count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());

        $this->assertEquals('Massive Art US', $webspace->getPortals()[0]->getName());

        $this->assertEquals(2, count($webspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $webspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals(
            '{language}.massiveart.{country}/{segment}',
            $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl()
        );

        $this->assertEquals('dev', $webspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals(
            'massiveart.lo/{localization}/{segment}',
            $webspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl()
        );

        $this->assertEquals('Massive Art CA', $webspace->getPortals()[1]->getName());
        $this->assertEquals('tree', $webspace->getPortals()[1]->getResourceLocatorStrategy());

        $this->assertEquals(2, count($webspace->getPortals()[1]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[1]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[1]->getLocalizations()[0]->getCountry());
        $this->assertEquals('fr', $webspace->getPortals()[1]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[1]->getLocalizations()[1]->getCountry());

        $this->assertEquals(2, count($webspace->getPortals()[1]->getEnvironments()));

        $this->assertEquals('prod', $webspace->getPortals()[1]->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($webspace->getPortals()[1]->getEnvironments()[0]->getUrls()));
        $this->assertEquals(
            '{language}.massiveart.{country}/{segment}',
            $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getUrl()
        );
        $this->assertEquals(null, $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getSegment());
        $this->assertEquals(null, $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[0]->getRedirect());

        $this->assertEquals(
            'www.massiveart.com',
            $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getUrl()
        );
        $this->assertEquals('ca', $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getCountry());
        $this->assertEquals('en', $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getLanguage());
        $this->assertEquals('s', $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getSegment());
        $this->assertEquals(null, $webspace->getPortals()[1]->getEnvironments()[0]->getUrls()[1]->getRedirect());

        $this->assertEquals('dev', $webspace->getPortals()[1]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[1]->getEnvironments()[1]->getUrls()));
        $this->assertEquals(
            'massiveart.lo/{localization}/{segment}',
            $webspace->getPortals()[1]->getEnvironments()[1]->getUrls()[0]->getUrl()
        );
    }

    public function testLoadWithoutPortalLocalizations()
    {
        $webspace = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Webspace/valid/sulu.io_withoutPortalLocalization.xml'
        );

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io_without_portal_localization', $webspace->getKey());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('uk', $webspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[0]->getChildren()[0]->getShadow());

        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('sulu', $webspace->getTheme()->getKey());
        $this->assertEquals(1, count($webspace->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $webspace->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals('short', $webspace->getPortals()[0]->getResourceLocatorStrategy());

        $this->assertEquals(3, count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getPortals()[0]->getLocalizations()[0]->getShadow());
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('uk', $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[1]->getShadow());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[2]->getLanguage());
        $this->assertEquals('at', $webspace->getPortals()[0]->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[2]->getShadow());

        $this->assertEquals(2, count($webspace->getPortals()[0]->getEnvironments()));

        $this->assertEquals('prod', $webspace->getPortals()[0]->getEnvironments()[0]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[0]->getEnvironments()[0]->getUrls()));
        $this->assertEquals(
            'sulu-without.at',
            $webspace->getPortals()[0]->getEnvironments()[0]->getUrls()[0]->getUrl()
        );

        $this->assertEquals('dev', $webspace->getPortals()[0]->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($webspace->getPortals()[0]->getEnvironments()[1]->getUrls()));
        $this->assertEquals(
            'sulu-without.lo',
            $webspace->getPortals()[0]->getEnvironments()[1]->getUrls()[0]->getUrl()
        );
    }

    /**
     * @expectedException \Sulu\Component\Webspace\Loader\Exception\InvalidUrlDefinitionException
     */
    public function testLoadWithIncorrectUrlDefinition()
    {
        $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Webspace/invalid/massiveart_withIncorrectUrls.xml'
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testLoadInvalid()
    {
        $this->loader->load(__DIR__ . '/../../../../Resources/DataFixtures/Webspace/invalid/massiveart.xml');
    }

    public function testLocalizations()
    {
        $localizations = $this->loader->load(
            __DIR__ . '/../../../../Resources/DataFixtures/Webspace/valid/massiveart.xml'
        )->getLocalizations();

        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertEquals('us', $localizations[0]->getCountry());
        $this->assertEquals('auto', $localizations[0]->getShadow());

        $this->assertEquals(1, count($localizations[0]->getChildren()));
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $localizations[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $localizations[0]->getChildren()[0]->getShadow());
        $this->assertEquals('en', $localizations[0]->getChildren()[0]->getParent()->getLanguage());
        $this->assertEquals('us', $localizations[0]->getChildren()[0]->getParent()->getCountry());
        $this->assertEquals('auto', $localizations[0]->getChildren()[0]->getParent()->getShadow());

        $this->assertEquals('fr', $localizations[1]->getLanguage());
        $this->assertEquals('ca', $localizations[1]->getCountry());
        $this->assertEquals(null, $localizations[1]->getShadow());
    }
}
