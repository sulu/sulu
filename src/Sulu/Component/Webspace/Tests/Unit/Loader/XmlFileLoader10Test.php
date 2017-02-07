<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Loader;

use Prophecy\Argument;
use Sulu\Component\Webspace\Exception\InvalidWebspaceException;
use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class XmlFileLoader10Test extends WebspaceTestCase
{
    /**
     * @var XmlFileLoader10
     */
    protected $loader;

    public function setUp()
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(function($arguments) {
            return $arguments[0];
        });

        $this->loader = new XmlFileLoader10($locator->reveal());
    }

    public function testSupports10()
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_deprecated.xml'
            )
        );
    }

    public function testSupports11()
    {
        $this->assertFalse(
            $this->loader->supports(
                $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io.xml'
            )
        );
    }

    public function testLoadDeprecated()
    {
        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_deprecated.xml'
        );

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->isDefault());

        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals(true, $webspace->getLocalizations()[1]->isDefault());

        $this->assertEquals('de_at', $webspace->getDefaultLocalization()->getLocalization());

        $this->assertEquals('sulu', $webspace->getTheme());
        $this->assertEquals(
            ['page' => 'default', 'homepage' => 'overview', 'home' => 'overview'],
            $webspace->getDefaultTemplates()
        );

        $this->assertEquals(2, count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals(true, $webspace->getPortals()[0]->getLocalizations()[1]->isDefault());

        $this->assertEquals('de_at', $webspace->getPortals()[0]->getDefaultLocalization()->getLocalization());

        $this->assertEquals(3, count($webspace->getPortals()[0]->getEnvironments()));

        $environmentProd = $webspace->getPortals()[0]->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertEquals(2, count($environmentProd->getUrls()));
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertTrue($environmentProd->getUrls()[0]->isMain());
        $this->assertEquals('de', $environmentProd->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getSegment());
        $this->assertEquals('at', $environmentProd->getUrls()[0]->getCountry());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getRedirect());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());
        $this->assertFalse($environmentProd->getUrls()[1]->isMain());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getSegment());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getCountry());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[1]->getRedirect());

        $environmentDev = $webspace->getPortals()[0]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertEquals(1, count($environmentDev->getUrls()));
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
        $this->assertTrue($environmentProd->getUrls()[0]->isMain());

        $environmentMain = $webspace->getPortals()[0]->getEnvironment('main');
        $this->assertEquals('main', $environmentMain->getType());
        $this->assertEquals(3, count($environmentMain->getUrls()));
        $this->assertEquals('sulu.lo', $environmentMain->getUrls()[0]->getUrl());
        $this->assertFalse($environmentMain->getUrls()[0]->isMain());
        $this->assertEquals('sulu.at', $environmentMain->getUrls()[1]->getUrl());
        $this->assertTrue($environmentMain->getUrls()[1]->isMain());
        $this->assertEquals('at.sulu.de', $environmentMain->getUrls()[2]->getUrl());
        $this->assertFalse($environmentMain->getUrls()[2]->isMain());

        $this->assertEquals(['page' => 'default', 'homepage' => 'overview', 'home' => 'overview'], $webspace->getDefaultTemplates());
        $this->assertEquals(['error-404' => 'test.html.twig', 'error' => 'test.html.twig'], $webspace->getTemplates());
    }

    public function testLoadWithInvalidWebspaceKey()
    {
        $this->setExpectedException(InvalidWebspaceException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/sulu.io_deprecated_invalid_webspace_key.xml'
        );
    }
}
