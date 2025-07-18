<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Loader;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Webspace\Exception\InvalidWebspaceException;
use Sulu\Component\Webspace\Loader\Exception\InvalidCustomUrlException;
use Sulu\Component\Webspace\Loader\Exception\InvalidUrlDefinitionException;
use Sulu\Component\Webspace\Loader\Exception\InvalidWebspaceDefaultLocalizationException;
use Sulu\Component\Webspace\Loader\Exception\PortalDefaultLocalizationNotFoundException;
use Sulu\Component\Webspace\Loader\Exception\WebspaceDefaultSegmentNotFoundException;
use Sulu\Component\Webspace\Loader\XmlFileLoader11;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Symfony\Component\Config\FileLocatorInterface;

class XmlFileLoader11Test extends WebspaceTestCase
{
    use ProphecyTrait;

    /**
     * @var XmlFileLoader11
     */
    protected $loader;

    public function setUp(): void
    {
        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate(Argument::any())->will(function($arguments) {
            return $arguments[0];
        });

        $this->loader = new XmlFileLoader11($locator->reveal());
    }

    public function testSupports10(): void
    {
        $this->assertFalse(
            $this->loader->supports(
                $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_deprecated.xml'
            )
        );
    }

    public function testSupports11(): void
    {
        $this->assertTrue(
            $this->loader->supports(
                $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io.xml'
            )
        );
    }

    public function testLoad(): void
    {
        $webspace = $this->loader->load($this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io.xml');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io', $webspace->getKey());
        $this->assertEquals('sulu_io', $webspace->getSecurity()->getSystem());
        $this->assertEquals(true, $webspace->getSecurity()->getPermissionCheck());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->isDefault());

        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals(true, $webspace->getLocalizations()[1]->isDefault());

        $this->assertEquals(['template1', 'template2'], $webspace->getExcludedTemplates());

        $this->assertEquals('de_at', $webspace->getDefaultLocalization()->getLocale());

        $this->assertEquals('sulu', $webspace->getTheme());
        $this->assertEquals(
            ['page' => 'default', 'homepage' => 'overview', 'home' => 'overview'],
            $webspace->getDefaultTemplates()
        );
        $this->assertEquals('short', $webspace->getResourceLocatorStrategy());

        $this->assertEquals(2, \count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals(true, $webspace->getPortals()[0]->getLocalizations()[1]->isDefault());

        $this->assertEquals('de_at', $webspace->getPortals()[0]->getDefaultLocalization()->getLocale());

        $this->assertEquals(4, \count($webspace->getPortals()[0]->getEnvironments()));

        $environmentProd = $webspace->getPortals()[0]->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertEquals(2, \count($environmentProd->getUrls()));
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

        $environmentTest = $webspace->getPortals()[0]->getEnvironment('test');
        $this->assertEquals('test', $environmentTest->getType());
        $this->assertEquals(2, \count($environmentTest->getUrls()));
        $this->assertEquals('sulu.at', $environmentTest->getUrls()[0]->getUrl());
        $this->assertTrue($environmentTest->getUrls()[0]->isMain());
        $this->assertEquals('de', $environmentTest->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $environmentTest->getUrls()[0]->getSegment());
        $this->assertEquals('at', $environmentTest->getUrls()[0]->getCountry());
        $this->assertEquals(null, $environmentTest->getUrls()[0]->getRedirect());
        $this->assertEquals('www.sulu.at', $environmentTest->getUrls()[1]->getUrl());
        $this->assertFalse($environmentTest->getUrls()[1]->isMain());
        $this->assertEquals(null, $environmentTest->getUrls()[1]->getLanguage());
        $this->assertEquals(null, $environmentTest->getUrls()[1]->getSegment());
        $this->assertEquals(null, $environmentTest->getUrls()[1]->getCountry());
        $this->assertEquals('sulu.at', $environmentTest->getUrls()[1]->getRedirect());

        $environmentDev = $webspace->getPortals()[0]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertEquals(1, \count($environmentDev->getUrls()));
        $this->assertEquals('sulu.lo', $environmentDev->getUrls()[0]->getUrl());
        $this->assertTrue($environmentProd->getUrls()[0]->isMain());

        $environmentMain = $webspace->getPortals()[0]->getEnvironment('main');
        $this->assertEquals('main', $environmentMain->getType());
        $this->assertEquals(3, \count($environmentMain->getUrls()));
        $this->assertEquals('sulu.lo', $environmentMain->getUrls()[0]->getUrl());
        $this->assertFalse($environmentMain->getUrls()[0]->isMain());
        $this->assertEquals('sulu.at', $environmentMain->getUrls()[1]->getUrl());
        $this->assertTrue($environmentMain->getUrls()[1]->isMain());
        $this->assertEquals('at.sulu.de', $environmentMain->getUrls()[2]->getUrl());
        $this->assertFalse($environmentMain->getUrls()[2]->isMain());

        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/massiveart.xml'
        );

        $this->assertEquals('Massive Art', $webspace->getName());
        $this->assertEquals('massiveart', $webspace->getKey());
        $this->assertEquals('massiveart', $webspace->getSecurity()->getSystem());
        $this->assertEquals(false, $webspace->getSecurity()->getPermissionCheck());

        $this->assertEquals('w', $webspace->getDefaultSegment()->getKey());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->isDefault());

        $this->assertEquals(1, \count($webspace->getLocalizations()[0]->getChildren()));
        $this->assertEquals('en', $webspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[0]->getChildren()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->getChildren()[0]->isDefault());

        $this->assertEquals('fr', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(true, $webspace->getLocalizations()[1]->isDefault());

        $this->assertEquals('de', $webspace->getLocalizations()[2]->getLanguage());
        $this->assertEquals(null, $webspace->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[2]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[2]->isDefault());

        $this->assertEquals('fr_ca', $webspace->getDefaultLocalization()->getLocale());

        $this->assertEquals('w', $webspace->getSegments()[0]->getKey());
        $this->assertEquals('Winter', $webspace->getSegments()[0]->getTitle('en'));
        $this->assertEquals('Winter', $webspace->getSegments()[0]->getTitle('de'));
        $this->assertEquals(true, $webspace->getSegments()[0]->isDefault());
        $this->assertEquals('s', $webspace->getSegments()[1]->getKey());
        $this->assertEquals('Summer', $webspace->getSegments()[1]->getTitle('en'));
        $this->assertEquals('Sommer', $webspace->getSegments()[1]->getTitle('de'));
        $this->assertEquals(false, $webspace->getSegments()[1]->isDefault());

        $this->assertEquals('massiveart', $webspace->getTheme());

        $this->assertEquals('tree_leaf_edit', $webspace->getResourceLocatorStrategy());

        $this->assertEquals(4, \count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals(false, $webspace->getPortals()[0]->getLocalizations()[0]->isDefault());
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals('fr', $webspace->getPortals()[0]->getLocalizations()[2]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[0]->getLocalizations()[2]->getCountry());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[3]->getLanguage());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[3]->getCountry());
        $this->assertEquals(true, $webspace->getPortals()[0]->getLocalizations()[3]->isDefault());

        $this->assertEquals(2, \count($webspace->getNavigation()->getContexts()));

        $this->assertEquals('main', $webspace->getNavigation()->getContexts()[0]->getKey());
        $this->assertEquals('Hauptnavigation', $webspace->getNavigation()->getContexts()[0]->getTitle('de'));
        $this->assertEquals('Mainnavigation', $webspace->getNavigation()->getContexts()[0]->getTitle('en'));
        $this->assertEquals('Main', $webspace->getNavigation()->getContexts()[0]->getTitle('fr'));

        $this->assertEquals('footer', $webspace->getNavigation()->getContexts()[1]->getKey());
        $this->assertEquals('Unten', $webspace->getNavigation()->getContexts()[1]->getTitle('de'));
        $this->assertEquals('Footer', $webspace->getNavigation()->getContexts()[1]->getTitle('en'));
        $this->assertEquals('Footer', $webspace->getNavigation()->getContexts()[1]->getTitle('fr'));

        $this->assertEquals('de', $webspace->getPortals()[0]->getDefaultLocalization()->getLocale());

        $this->assertEquals('Massive Art US', $webspace->getPortals()[0]->getName());

        $this->assertEquals(2, \count($webspace->getPortals()[0]->getEnvironments()));

        $environmentProd = $webspace->getPortals()[0]->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals(
            '{language}.massiveart.{country}',
            $environmentProd->getUrls()[0]->getUrl()
        );

        $environmentDev = $webspace->getPortals()[0]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals(
            'massiveart.lo/{localization}',
            $environmentDev->getUrls()[0]->getUrl()
        );

        $this->assertEquals('Massive Art CA', $webspace->getPortals()[1]->getName());
        $this->assertEquals('tree_leaf_edit', $webspace->getResourceLocatorStrategy());

        $this->assertEquals(2, \count($webspace->getPortals()[1]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[1]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[1]->getLocalizations()[0]->getCountry());
        $this->assertEquals(true, $webspace->getPortals()[1]->getLocalizations()[0]->isDefault());
        $this->assertEquals('fr', $webspace->getPortals()[1]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('ca', $webspace->getPortals()[1]->getLocalizations()[1]->getCountry());
        $this->assertEquals(false, $webspace->getPortals()[1]->getLocalizations()[1]->isDefault());

        $this->assertEquals('en_ca', $webspace->getPortals()[1]->getDefaultLocalization()->getLocale());

        $this->assertEquals(2, \count($webspace->getPortals()[1]->getEnvironments()));

        $environmentProd = $webspace->getPortals()[1]->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(2, $environmentProd->getUrls());
        $this->assertEquals(
            '{language}.massiveart.{country}',
            $environmentProd->getUrls()[0]->getUrl()
        );
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getCountry());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getSegment());
        $this->assertEquals(null, $environmentProd->getUrls()[0]->getRedirect());

        $this->assertEquals(
            'www.massiveart.com',
            $environmentProd->getUrls()[1]->getUrl()
        );
        $this->assertEquals('ca', $environmentProd->getUrls()[1]->getCountry());
        $this->assertEquals('en', $environmentProd->getUrls()[1]->getLanguage());
        $this->assertEquals(null, $environmentProd->getUrls()[1]->getRedirect());

        $environmentDev = $webspace->getPortals()[1]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals(
            'massiveart.lo/{localization}',
            $environmentDev->getUrls()[0]->getUrl()
        );
    }

    public function testLoadNoStrategy(): void
    {
        $webspace = $this->loader->load($this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_no_strategy.xml');

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('tree_leaf_edit', $webspace->getResourceLocatorStrategy());
    }

    public function testLoadWithoutPortalLocalizations(): void
    {
        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_withoutPortalLocalization.xml'
        );

        $this->assertEquals('Sulu CMF', $webspace->getName());
        $this->assertEquals('sulu_io_without_portal_locale', $webspace->getKey());

        $this->assertNull($webspace->getSecurity());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getLocalizations()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->isDefault());

        $this->assertEquals('en', $webspace->getLocalizations()[0]->getChildren()[0]->getLanguage());
        $this->assertEquals('uk', $webspace->getLocalizations()[0]->getChildren()[0]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[0]->getChildren()[0]->getShadow());
        $this->assertEquals(false, $webspace->getLocalizations()[0]->getChildren()[0]->isDefault());

        $this->assertEquals('de', $webspace->getLocalizations()[1]->getLanguage());
        $this->assertEquals('at', $webspace->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getLocalizations()[1]->getShadow());
        $this->assertEquals(true, $webspace->getLocalizations()[1]->isDefault());

        $this->assertEquals('de_at', $webspace->getDefaultLocalization()->getLocale());

        $this->assertEquals('sulu', $webspace->getTheme());

        $this->assertEquals('short', $webspace->getResourceLocatorStrategy());

        $this->assertEquals(3, \count($webspace->getPortals()[0]->getLocalizations()));
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[0]->getLanguage());
        $this->assertEquals('us', $webspace->getPortals()[0]->getLocalizations()[0]->getCountry());
        $this->assertEquals('auto', $webspace->getPortals()[0]->getLocalizations()[0]->getShadow());
        $this->assertEquals(false, $webspace->getPortals()[0]->getLocalizations()[0]->isDefault());
        $this->assertEquals('en', $webspace->getPortals()[0]->getLocalizations()[1]->getLanguage());
        $this->assertEquals('uk', $webspace->getPortals()[0]->getLocalizations()[1]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[1]->getShadow());
        $this->assertEquals(false, $webspace->getPortals()[0]->getLocalizations()[1]->isDefault());
        $this->assertEquals('de', $webspace->getPortals()[0]->getLocalizations()[2]->getLanguage());
        $this->assertEquals('at', $webspace->getPortals()[0]->getLocalizations()[2]->getCountry());
        $this->assertEquals(null, $webspace->getPortals()[0]->getLocalizations()[2]->getShadow());
        $this->assertEquals(true, $webspace->getPortals()[0]->getLocalizations()[2]->isDefault());

        $this->assertEquals('de_at', $webspace->getPortals()[0]->getDefaultLocalization()->getLocale());

        $this->assertCount(2, $webspace->getPortals()[0]->getEnvironments());

        $environmentProd = $webspace->getPortals()[0]->getEnvironment('prod');
        $this->assertEquals('prod', $environmentProd->getType());
        $this->assertCount(1, $environmentProd->getUrls());
        $this->assertEquals(
            'sulu-without.at',
            $environmentProd->getUrls()[0]->getUrl()
        );

        $environmentDev = $webspace->getPortals()[0]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertCount(1, $environmentDev->getUrls());
        $this->assertEquals(
            'sulu-without.lo',
            $environmentDev->getUrls()[0]->getUrl()
        );
    }

    public function testLoadWithIncorrectUrlDefinition(): void
    {
        $this->expectException(InvalidUrlDefinitionException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart_withIncorrectUrls.xml'
        );
    }

    public function testLoadInvalid(): void
    {
        $this->expectExceptionMessage('Could not parse webspace XML file');
        $this->expectException(InvalidWebspaceException::class);
        $this->loader->load($this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart.xml');
    }

    public function testLoadWithNotExistingDefault(): void
    {
        $this->expectException(PortalDefaultLocalizationNotFoundException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart_withNotExistingDefault.xml'
        );
    }

    public function testLoadWithoutDefaultSegment(): void
    {
        $this->expectException(WebspaceDefaultSegmentNotFoundException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart_withNotExistingDefaultSegment.xml'
        );
    }

    public function testLoadWithTwoDefaultLocalization(): void
    {
        $this->expectException(InvalidWebspaceDefaultLocalizationException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart_withTwoDefaultLocalizations.xml'
        );
    }

    public function testLocalizations(): void
    {
        $localizations = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/massiveart.xml'
        )->getLocalizations();

        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertEquals('us', $localizations[0]->getCountry());
        $this->assertEquals('auto', $localizations[0]->getShadow());

        $this->assertEquals(1, \count($localizations[0]->getChildren()));
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

    public function testSingleLanguage(): void
    {
        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_singleLanguage.xml'
        );

        $localizations = $webspace->getLocalizations();

        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertNull($localizations[0]->getCountry());
        $this->assertNull($localizations[0]->getShadow());
        $this->assertNull($localizations[0]->getParent());

        $prodUrl = $webspace->getPortals()[0]->getEnvironment('prod')->getUrls()[0];

        $this->assertEquals('sulu-single-language.at', $prodUrl->getUrl());
        $this->assertEquals('en', $prodUrl->getLanguage());
        $this->assertNull($prodUrl->getCountry());
        $this->assertNull($prodUrl->getRedirect());
        $this->assertNull($prodUrl->getSegment());

        $devUrl = $webspace->getPortals()[0]->getEnvironment('dev')->getUrls()[0];

        $this->assertEquals('sulu-single-language.lo', $devUrl->getUrl());
        $this->assertEquals('en', $devUrl->getLanguage());
        $this->assertNull($devUrl->getCountry());
        $this->assertNull($devUrl->getRedirect());
        $this->assertNull($devUrl->getSegment());
    }

    public function testLoadWithInvalidWebspaceKey(): void
    {
        $this->expectException(InvalidWebspaceException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/sulu.io_invalid_webspace_key.xml'
        );
    }

    public function testTemplateWithNonUniqueType(): void
    {
        $this->expectException(InvalidWebspaceException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/sulu.io_multiple_template_types.xml'
        );
    }

    public function testUrlWithTrailingSlash(): void
    {
        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/url-with-trailing-slash/sulu.io_url_with_slash.xml'
        );

        $environmentDev = $webspace->getPortals()[0]->getEnvironment('dev');
        $this->assertEquals('dev', $environmentDev->getType());
        $this->assertEquals(2, \count($environmentDev->getUrls()));
        $this->assertEquals('sulu-without-slash.lo', $environmentDev->getUrls()[0]->getUrl());
        $this->assertEquals('sulu-with-slash.lo', $environmentDev->getUrls()[1]->getUrl());
    }

    public function testInvalidCustomUrl(): void
    {
        $this->expectException(InvalidCustomUrlException::class);

        $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/invalid/massiveart_invalid_custom_url.xml'
        );
    }

    public function testLoadDynamicOrder(): void
    {
        $webspace = $this->loader->load(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/valid/sulu.io_dynamicOrder.xml'
        );

        $this->assertEquals('Sulu CMF', $webspace->getName());
    }
}
