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
use Sulu\Component\Webspace\Loader\XmlFileLoader10;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Manager\WebspaceManager;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url\Replacer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Filesystem\Filesystem;

class WebspaceManagerTest extends WebspaceTestCase
{
    /**
     * @var DelegatingLoader
     */
    protected $loader;

    /**
     * @var WebspaceManager
     */
    protected $webspaceManager;

    /**
     * @var string
     */
    private $cacheDirectory;

    public function setUp()
    {
        $this->cacheDirectory = $this->getResourceDirectory() . '/cache';

        if (file_exists($this->cacheDirectory)) {
            $filesystem = new Filesystem();
            $filesystem->remove($this->cacheDirectory);
        }

        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(function($arguments) {
            return $arguments[0];
        });

        $resolver = new LoaderResolver([
            new XmlFileLoader11($locator->reveal()),
            new XmlFileLoader10($locator->reveal()),
        ]);

        $this->loader = new DelegatingLoader($resolver);

        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            [
                'cache_dir' => $this->cacheDirectory,
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/valid',
                'cache_class' => 'WebspaceCollectionCache' . uniqid(),
            ]
        );
    }

    public function testGetAll()
    {
        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $webspace = $webspaces->getWebspace('massiveart');

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());
        $this->assertEquals('massiveart', $webspace->getSecurity()->getSystem());

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

        $this->assertEquals('massiveart', $webspace->getTheme());

        $this->assertEquals(2, count($webspace->getNavigation()->getContexts()));

        $this->assertEquals('main', $webspace->getNavigation()->getContexts()[0]->getKey());
        $this->assertEquals('Hauptnavigation', $webspace->getNavigation()->getContexts()[0]->getTitle('de'));
        $this->assertEquals('Mainnavigation', $webspace->getNavigation()->getContexts()[0]->getTitle('en'));
        $this->assertEquals('Main', $webspace->getNavigation()->getContexts()[0]->getTitle('fr'));

        $this->assertEquals('footer', $webspace->getNavigation()->getContexts()[1]->getKey());
        $this->assertEquals('Unten', $webspace->getNavigation()->getContexts()[1]->getTitle('de'));
        $this->assertEquals('Footer', $webspace->getNavigation()->getContexts()[1]->getTitle('en'));
        $this->assertEquals('Footer', $webspace->getNavigation()->getContexts()[1]->getTitle('fr'));

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals(4, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('en', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[2]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[2]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[2]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[3]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[3]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[3]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $environmentProd->getUrls()[0]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $environmentDev->getUrls()[0]->getUrl());

        $portal = $webspace->getPortals()[1];

        $this->assertEquals('Massive Art CA', $portal->getName());
        $this->assertEquals('massiveart_ca', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(2, count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertEquals(2, count($environmentProd->getUrls()));
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getCountry());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getSegment());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getRedirect());
        $this->assertEquals('www.massiveart.com', $environmentProd->getUrls()[1]->getUrl());
        $this->assertEquals('en', $environmentProd->getUrls()[1]->getLanguage());
        $this->assertEquals('ca', $environmentProd->getUrls()[1]->getCountry());
        $this->assertEquals('s', $environmentProd->getUrls()[1]->getSegment());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getRedirect());

        $environmentProd = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $environmentProd->getUrls()[0]->getUrl());
    }

    public function testFindWebspaceByKey()
    {
        $webspace = $this->webspaceManager->findWebspaceByKey('sulu_io');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());

        $this->assertEquals(2, count($webspace->getLocalizations()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());

        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $webspace->getPortals()[0];

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(3, count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[1]->getRedirect());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindPortalByKey()
    {
        $portal = $this->webspaceManager->findPortalByKey('sulucmf_at');

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindWebspaceByNotExistingKey()
    {
        $portal = $this->webspaceManager->findWebspaceByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalByNotExistingKey()
    {
        $portal = $this->webspaceManager->findPortalByKey('not_existing');
        $this->assertNull($portal);
    }

    public function testFindPortalInformationByUrl()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.at/test/test/test', 'prod');
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocalization());
        $this->assertNull($portalInformation->getSegment());

        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        /** @var Portal $portal */
        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocalization());
        $this->assertNull($portalInformation->getSegment());

        /* @var Portal $portal */
        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(3, count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testFindPortalInformationsByUrl()
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByUrl('sulu.at/test/test/test', 'prod');
        $portalInformation = reset($portalInformations);
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocalization());
        $this->assertNull($portalInformation->getSegment());

        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        /** @var Portal $portal */
        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertCount(3, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());

        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('sulu.lo', 'dev');
        $this->assertEquals('de_at', $portalInformation->getLocalization()->getLocalization());
        $this->assertNull($portalInformation->getSegment());

        /* @var Portal $portal */
        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());

        $portal = $portalInformation->getPortal();

        $this->assertEquals('Sulu CMF AT', $portal->getName());
        $this->assertEquals('sulucmf_at', $portal->getKey());

        $this->assertEquals(2, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $portal->getLocalizations()[1]->getShadow());

        $this->assertEquals(3, count($portal->getEnvironments()));

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals('sulu.at', $environmentProd->getUrls()[0]->getUrl());
        $this->assertEquals('www.sulu.at', $environmentProd->getUrls()[1]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
    }

    public function provideFindPortalInformationByUrl()
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
     */
    public function testFindPortalInformationByUrlWithInvalidSuffix($url, $shouldFind)
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl($url, 'dev');

        if ($shouldFind) {
            $this->assertNotNull($portalInformation);
        } else {
            $this->assertNull($portalInformation);
        }
    }

    public function testFindPortalInformationsByWebspaceKeyAndLocale()
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByWebspaceKeyAndLocale(
            'sulu_io',
            'de_at',
            'dev'
        );

        $this->assertCount(1, $portalInformations);
        $portalInformation = reset($portalInformations);

        $this->assertEquals('sulu_io', $portalInformation->getWebspace()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testFindPortalInformationsByPortalKeyAndLocale()
    {
        $portalInformations = $this->webspaceManager->findPortalInformationsByPortalKeyAndLocale(
            'sulucmf_at',
            'de_at',
            'dev'
        );

        $this->assertCount(1, $portalInformations);

        $portalInformation = reset($portalInformations);
        $this->assertEquals('sulucmf_at', $portalInformation->getPortal()->getKey());
        $this->assertEquals('de_at', $portalInformation->getLocale());
    }

    public function testFindPortalInformationByUrlWithSegment()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('en.massiveart.us/w/about-us', 'prod');
        $this->assertEquals('en_us', $portalInformation->getLocalization()->getLocalization());
        $this->assertEquals('winter', $portalInformation->getSegment()->getName());

        /** @var Portal $portal */
        $portal = $portalInformation->getPortal();

        $this->assertEquals('Massive Art US', $portal->getName());
        $this->assertEquals('massiveart_us', $portal->getKey());

        $this->assertEquals(4, count($portal->getLocalizations()));
        $this->assertEquals('en', $portal->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $portal->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[0]->getShadow());
        $this->assertEquals('en', $portal->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[1]->getShadow());
        $this->assertEquals('fr', $portal->getLocalizations()[2]->getLanguage());
        $this->assertEquals('ca', $portal->getLocalizations()[2]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[2]->getShadow());
        $this->assertEquals('de', $portal->getLocalizations()[3]->getLanguage());
        $this->assertEquals(null, $portal->getLocalizations()[3]->getCountry());
        $this->assertEquals(false, $portal->getLocalizations()[3]->getShadow());

        $this->assertCount(2, $portal->getEnvironments());

        $environmentProd = $portal->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals('{language}.massiveart.{country}/{segment}', $environmentProd->getUrls()[0]->getUrl());

        $environmentDev = $portal->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals('massiveart.lo/{localization}/{segment}', $environmentDev->getUrls()[0]->getUrl());
    }

    public function testLoadMultiple()
    {
        $this->webspaceManager = new WebspaceManager(
            $this->loader,
            new Replacer(),
            [
                'cache_dir' => $this->getResourceDirectory() . '/cache',
                'config_dir' => $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple',
                'cache_class' => 'WebspaceCollectionCache' . uniqid(),
            ]
        );

        $webspaces = $this->webspaceManager->getWebspaceCollection();

        $this->assertEquals(2, $webspaces->length());

        $webspace = $webspaces->getWebspace('massiveart');

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());

        $webspace = $webspaces->getWebspace('sulu_io');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
    }

    public function testRedirectUrl()
    {
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl('www.sulu.at/test/test', 'prod');

        $this->assertEquals('sulu.at', $portalInformation->getRedirect());
        $this->assertEquals('www.sulu.at', $portalInformation->getUrl());

        /** @var Webspace $webspace */
        $webspace = $portalInformation->getWebspace();

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertCount(2, $webspace->getLocalizations());
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals('', $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals('sulu', $webspace->getTheme());
    }

    public function testLocalizations()
    {
        $localizations = $this->webspaceManager->findWebspaceByKey('massiveart')->getLocalizations();

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

        $allLocalizations = $this->webspaceManager->findWebspaceByKey('massiveart')->getAllLocalizations();
        $this->assertEquals('en', $allLocalizations[0]->getLanguage());
        $this->assertEquals('us', $allLocalizations[0]->getCountry());
        $this->assertEquals('auto', $allLocalizations[0]->getShadow());
        $this->assertEquals('en', $allLocalizations[1]->getLanguage());
        $this->assertEquals('ca', $allLocalizations[1]->getCountry());
        $this->assertEquals(null, $allLocalizations[1]->getShadow());
        $this->assertEquals('fr', $allLocalizations[2]->getLanguage());
        $this->assertEquals('ca', $allLocalizations[2]->getCountry());
        $this->assertEquals(null, $allLocalizations[2]->getShadow());
    }

    public function testFindUrlsByResourceLocator()
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'en_us', 'massiveart');

        $this->assertCount(2, $result);
        $this->assertContains('http://massiveart.lo/en-us/w/test', $result);
        $this->assertContains('http://massiveart.lo/en-us/s/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals(['http://sulu.lo/test'], $result);
    }

    public function testFindUrlsByResourceLocatorWithScheme()
    {
        $result = $this->webspaceManager->findUrlsByResourceLocator(
            '/test',
            'dev',
            'en_us',
            'massiveart',
            null,
            'https'
        );

        $this->assertCount(2, $result);
        $this->assertContains('https://massiveart.lo/en-us/w/test', $result);
        $this->assertContains('https://massiveart.lo/en-us/s/test', $result);

        $result = $this->webspaceManager->findUrlsByResourceLocator('/test', 'dev', 'de_at', 'sulu_io', null, 'https');
        $this->assertEquals(['https://sulu.lo/test'], $result);
    }

    public function testFindUrlByResourceLocator()
    {
        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'dev', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.lo/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io');
        $this->assertEquals('http://sulu.at/test', $result);

        $result = $this->webspaceManager->findUrlByResourceLocator('/test', 'main', 'de_at', 'sulu_io', 'sulu.lo');
        $this->assertEquals('http://sulu.lo/test', $result);

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

    public function testGetPortals()
    {
        $portals = $this->webspaceManager->getPortals();

        $this->assertCount(6, $portals);
        $this->assertEquals('massiveart_us', $portals['massiveart_us']->getKey());
        $this->assertEquals('massiveart_ca', $portals['massiveart_ca']->getKey());
        $this->assertEquals('sulucmf_at', $portals['sulucmf_at']->getKey());
        $this->assertEquals('dancmf_at', $portals['dancmf_at']->getKey());
        $this->assertEquals('sulucmf_singlelanguage_at', $portals['sulucmf_singlelanguage_at']->getKey());
        $this->assertEquals(
            'sulucmf_withoutportallocalizations_at',
            $portals['sulucmf_withoutportallocalizations_at']->getKey()
        );
    }

    public function testGetUrls()
    {
        $urls = $this->webspaceManager->getUrls('dev');

        $this->assertCount(13, $urls);
        $this->assertContains('sulu.lo', $urls);
        $this->assertContains('sulu-single-language.lo', $urls);
        $this->assertContains('sulu-without.lo', $urls);
        $this->assertContains('massiveart.lo', $urls);
        $this->assertContains('massiveart.lo/en-us/w', $urls);
        $this->assertContains('massiveart.lo/en-us/s', $urls);
        $this->assertContains('massiveart.lo/en-ca/w', $urls);
        $this->assertContains('massiveart.lo/en-ca/s', $urls);
        $this->assertContains('massiveart.lo/fr-ca/w', $urls);
        $this->assertContains('massiveart.lo/fr-ca/s', $urls);
        $this->assertContains('massiveart.lo/de/w', $urls);
        $this->assertContains('massiveart.lo/de/s', $urls);
    }

    public function testGetPortalInformations()
    {
        $portalInformations = $this->webspaceManager->getPortalInformations('dev');

        $this->assertCount(13, $portalInformations);
        $this->assertArrayHasKey('sulu.lo', $portalInformations);
        $this->assertArrayHasKey('sulu-single-language.lo', $portalInformations);
        $this->assertArrayHasKey('sulu-without.lo', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-us/w', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-us/s', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-ca/w', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/en-ca/s', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/fr-ca/w', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/fr-ca/s', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/de/w', $portalInformations);
        $this->assertArrayHasKey('massiveart.lo/de/s', $portalInformations);
    }

    public function testGetAllLocalizations()
    {
        $localizations = $this->webspaceManager->getAllLocalizations();

        array_walk(
            $localizations,
            function (&$localization) {
                $localization = $localization->toArray();
                unset($localization['children']);
                unset($localization['localization']);
                unset($localization['shadow']);
                unset($localization['default']);
                unset($localization['xDefault']);
            }
        );

        // check for duplicates
        $this->assertCount(7, $localizations);

        $this->assertContains(
            [
                'country' => 'us',
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'at',
                'language' => 'de',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'ca',
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'ca',
                'language' => 'fr',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => null,
                'language' => 'de',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => null,
                'language' => 'en',
            ],
            $localizations
        );
        $this->assertContains(
            [
                'country' => 'uk',
                'language' => 'en',
            ],
            $localizations
        );
    }

    public function testGetAllLocalesByWebspaces()
    {
        $webspacesLocales = $this->webspaceManager->getAllLocalesByWebspaces();

        foreach ($webspacesLocales as &$webspaceLocales) {
            array_walk(
                $webspaceLocales,
                function (&$webspaceLocale) {
                    $webspaceLocale = $webspaceLocale->toArray();
                    unset($webspaceLocale['children']);
                    unset($webspaceLocale['localization']);
                    unset($webspaceLocale['shadow']);
                    unset($webspaceLocale['default']);
                    unset($webspaceLocale['xDefault']);
                }
            );
        }

        $this->assertArrayHasKey('sulu_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['sulu_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['sulu_io']);
        $this->assertEquals(['country' => 'us', 'language' => 'en'], $webspacesLocales['sulu_io']['en_us']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], reset($webspacesLocales['sulu_io']));

        $this->assertArrayHasKey('massiveart', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('en_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('fr_ca', $webspacesLocales['massiveart']);
        $this->assertArrayHasKey('de', $webspacesLocales['massiveart']);
        $this->assertEquals(['country' => 'ca', 'language' => 'fr'], reset($webspacesLocales['massiveart']));

        $this->assertArrayHasKey('dan_io', $webspacesLocales);
        $this->assertArrayHasKey('en_us', $webspacesLocales['dan_io']);
        $this->assertArrayHasKey('de_at', $webspacesLocales['dan_io']);
        $this->assertEquals(['country' => 'at', 'language' => 'de'], reset($webspacesLocales['dan_io']));
    }
}
