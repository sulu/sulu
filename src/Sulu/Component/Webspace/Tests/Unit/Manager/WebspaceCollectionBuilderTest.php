<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Manager;

use Sulu\Bundle\WebsiteBundle\DependencyInjection\Configuration;
use Sulu\Bundle\WebsiteBundle\DependencyInjection\SuluWebsiteExtension;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Exception\InvalidTemplateException;
use Sulu\Component\Webspace\Manager\PortalInformationBuilder;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceCollectionBuilder;
use Sulu\Component\Webspace\NavigationContext;
use Sulu\Component\Webspace\Tests\Unit\WebspaceTestCase;
use Sulu\Component\Webspace\Url\Replacer;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class WebspaceCollectionBuilderTest extends WebspaceTestCase
{
    /** @param array<string> $files */
    private function loadCollection(string $directory, array $files): WebspaceCollection
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension(new SuluWebsiteExtension());

        $loader = new XmlFileLoader($containerBuilder, new FileLocator($directory));
        foreach ($files as $file) {
            $loader->load($file);
        }

        $configuration = $containerBuilder->getExtensionConfig('sulu_website');

        $processor = new Processor();
        $finalConfiguration = $processor->processConfiguration(new Configuration(), $configuration);

        return (new WebspaceCollectionBuilder(
            new PortalInformationBuilder(new Replacer()),
            $finalConfiguration['webspaces'],
        ))->build();
    }

    public function testBuildAll(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple',
            ['massiveart.xml', 'sulu.io.xml']
        );

        $webspaces = \array_values($webspaceCollection->getWebspaces());

        $this->assertCount(2, $webspaces);

        $this->assertEquals('Massive Art', $webspaces[0]->getName());
        $this->assertEquals('Sulu CMF', $webspaces[1]->getName());

        /** @var NavigationContext $navigationContext */
        $navigationContext = $webspaces[0]->getNavigation()->getContexts();
        $this->assertEquals(2, \count($navigationContext));

        $this->assertEquals('main', $navigationContext[0]->getKey());
        $this->assertEquals('Hauptnavigation', $navigationContext[0]->getTitle('de'));
        $this->assertEquals('Mainnavigation', $navigationContext[0]->getTitle('en'));
        $this->assertEquals('Main', $navigationContext[0]->getTitle('fr'));

        $this->assertEquals('footer', $navigationContext[1]->getKey());
        $this->assertEquals('Unten', $navigationContext[1]->getTitle('de'));
        $this->assertEquals('Footer', $navigationContext[1]->getTitle('en'));
        $this->assertEquals('Footer', $navigationContext[1]->getTitle('fr'));

        $portals = \array_values($webspaceCollection->getPortals());

        $this->assertCount(3, $portals);

        $this->assertEquals('Massive Art US', $portals[0]->getName());
        $this->assertEquals('Massive Art CA', $portals[1]->getName());
        $this->assertEquals('Sulu CMF AT', $portals[2]->getName());

        $prodPortalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertCount(7, $prodPortalInformations);

        $prodPortalInformationKeys = \array_keys($prodPortalInformations);
        $prodPortalInformationValues = \array_values($prodPortalInformations);

        // the values before have the same size, therefore the order cannot be determined
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[0]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[1]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[2]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[3]->getType());
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[4]->getType());
        $this->assertEquals('www.sulu.at', $prodPortalInformationKeys[5]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_REDIRECT, $prodPortalInformationValues[5]->getType());
        $this->assertEquals('sulu.at', $prodPortalInformationKeys[6]);
        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $prodPortalInformationValues[6]->getType());

        $devPortalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertCount(8, $devPortalInformations);

        $devPortalInformationValues = [];
        foreach ($devPortalInformations as $portalInformation) {
            $devPortalInformationValues[$portalInformation->getUrl()] = \array_filter([
                'type' => $portalInformation->getType(),
                'redirect' => $portalInformation->getRedirect(),
                'locale' => $portalInformation->getLocale(),
            ]);
        }

        // massiveart-ca.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-ca.lo/en-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-ca.lo/fr-ca']
        );

        // massiveart-us.lo
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_ca'],
            $devPortalInformationValues['massiveart-us.lo/en-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'en_us'],
            $devPortalInformationValues['massiveart-us.lo/en-us']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'fr_ca'],
            $devPortalInformationValues['massiveart-us.lo/fr-ca']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-ca.lo/{localization}'],
            $devPortalInformationValues['massiveart-ca.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL, 'redirect' => 'massiveart-us.lo/{localization}'],
            $devPortalInformationValues['massiveart-us.lo']
        );
        $this->assertEquals(
            ['type' => RequestAnalyzerInterface::MATCH_TYPE_FULL, 'locale' => 'de_at'],
            $devPortalInformationValues['sulu.lo']
        );
    }

    public function testChildLocalizations(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple',
            ['massiveart.xml']
        );

        $webspace = $webspaceCollection->getWebspace('massiveart');
        $localizations = $webspace->getLocalizations();

        // Checking the main locales
        $this->assertCount(2, $localizations);
        $this->assertEquals('en', $localizations[0]->getLanguage());
        $this->assertEquals('us', $localizations[0]->getCountry());
        $this->assertEquals('auto', $localizations[0]->getShadow());

        $this->assertEquals('fr', $localizations[1]->getLanguage());
        $this->assertEquals('ca', $localizations[1]->getCountry());
        $this->assertEquals(null, $localizations[1]->getShadow());

        // Checking the child locale
        $this->assertEquals(1, \count($localizations[0]->getChildren()));
        $childLocalization = $localizations[0]->getChildren()[0];
        $this->assertEquals('en', $childLocalization->getLanguage());
        $this->assertEquals('ca', $childLocalization->getCountry());
        $this->assertEquals(null, $childLocalization->getShadow());
        $this->assertEquals($localizations[0], $childLocalization->getParent());

        $this->assertEquals(0, \count($localizations[1]->getChildren()));

        // Checking list of all locales
        $this->assertEquals(
            [$localizations[0], $childLocalization, $localizations[1]],
            $webspace->getAllLocalizations()
        );
    }

    public function testBuildWithMultipleLocalizationUrls(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/multiple-localization-urls',
            ['sulu.io.xml']
        );

        $portalInformations = $webspaceCollection->getPortalInformations('prod');

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.de']->getType());
        $this->assertEquals('sulu.de', $portalInformations['sulu.de']->getUrl());
        $this->assertEquals('de', $portalInformations['sulu.de']->getLocalization()->getLocale());

        $this->assertEquals(RequestAnalyzerInterface::MATCH_TYPE_FULL, $portalInformations['sulu.us']->getType());
        $this->assertEquals('sulu.us', $portalInformations['sulu.us']->getUrl());
        $this->assertEquals('en', $portalInformations['sulu.us']->getLocalization()->getLocale());
    }

    public function testBuildWithMainUrl(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/main',
            ['sulu.io.xml']
        );

        $webspace = $webspaceCollection->getWebspaces()['sulu_io'];
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

    public function testBuildWithCustomUrl(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/custom-url',
            ['sulu.io.xml']
        );

        $webspace = $webspaceCollection->getWebspaces()['sulu_io'];
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

    public function testLanguageSpecificPartial(): void
    {
        $webspaceCollection = $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/language-specific',
            ['sulu.io.xml']
        );

        $portalInformations = $webspaceCollection->getPortalInformations('dev');

        $this->assertSame([
            'austria.sulu.io/de',
            'austria.sulu.io',
            'usa.sulu.io/en',
            'usa.sulu.io',
        ], \array_keys($portalInformations));

        $this->assertSame($portalInformations['austria.sulu.io/de']->getPriority(), 10);
        $this->assertSame($portalInformations['austria.sulu.io']->getPriority(), 9);
        $this->assertSame($portalInformations['usa.sulu.io/en']->getPriority(), 10);
        $this->assertSame($portalInformations['usa.sulu.io']->getPriority(), 9);
    }

    public function testThrowForMissingDefaultTemplate(): void
    {
        $this->expectException(InvalidTypeException::class);

        $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/missing-default-template',
            ['sulu.xml']
        );
    }

    public function testThrowForMissingExcludedTemplate(): void
    {
        $this->expectException(InvalidTemplateException::class);

        $this->loadCollection(
            $this->getResourceDirectory() . '/DataFixtures/Webspace/excluded-default-template',
            ['sulu.xml']
        );
    }
}
