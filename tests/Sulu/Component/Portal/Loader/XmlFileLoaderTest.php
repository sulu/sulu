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
        $portal = $this->loader->load(__DIR__.'/../../../../Resources/DataFixtures/sulu.io.xml');

        $this->assertEquals('Sulu CMF', $portal->getName());
        $this->assertEquals('sulu_io', $portal->getKey());

        $this->assertEquals(2, count($portal->getLanguages()));
        $this->assertEquals('en', $portal->getLanguages()[0]->getCode());
        $this->assertEquals(true, $portal->getLanguages()[0]->getMain());
        $this->assertEquals(false, $portal->getLanguages()[0]->getFallback());
        $this->assertEquals('de', $portal->getLanguages()[1]->getCode());
        $this->assertEquals(false, $portal->getLanguages()[1]->getMain());
        $this->assertEquals(true, $portal->getLanguages()[1]->getFallback());

        $this->assertEquals('sulu', $portal->getTheme()->getKey());
        $this->assertEquals(1, count($portal->getTheme()->getExcludedTemplates()));
        $this->assertEquals('overview', $portal->getTheme()->getExcludedTemplates()[0]);

        $this->assertEquals(2, count($portal->getEnvironments()));

        $this->assertEquals('prod', $portal->getEnvironments()[0]->getType());
        $this->assertEquals(2, count($portal->getEnvironments()[0]->getUrls()));
        $this->assertEquals('sulu.io', $portal->getEnvironments()[0]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
        $this->assertEquals('www.sulu.io', $portal->getEnvironments()[0]->getUrls()[1]->getUrl());
        $this->assertEquals(false, $portal->getEnvironments()[0]->getUrls()[1]->isMain());

        $this->assertEquals('dev', $portal->getEnvironments()[1]->getType());
        $this->assertEquals(1, count($portal->getEnvironments()[1]->getUrls()));
        $this->assertEquals('sulu.lo', $portal->getEnvironments()[1]->getUrls()[0]->getUrl());
        $this->assertEquals(true, $portal->getEnvironments()[0]->getUrls()[0]->isMain());
    }
}
