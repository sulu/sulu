<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Tests\Unit\Manager;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManager;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

class LocalizationManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var LocalizationManagerInterface
     */
    private $localizationManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->localizationManager = new LocalizationManager();
    }

    public function testGetAllLocalizations(): void
    {
        $localization1 = new Localization();
        $localization1->setLanguage('de');
        $localization2 = new Localization();
        $localization2->setLanguage('en');
        $localization3 = new Localization();
        $localization3->setLanguage('fr');

        $this->addLocalizationProvider([$localization1, $localization2]);
        $this->addLocalizationProvider([$localization3]);

        $localizations = $this->localizationManager->getLocalizations();

        $this->assertCount(3, $localizations);
        $this->assertContains($localization1, $localizations);
        $this->assertContains($localization2, $localizations);
        $this->assertContains($localization3, $localizations);
    }

    public function testGetAllLocalizationsWithSameLocalizations(): void
    {
        $localization1 = new Localization();
        $localization1->setLanguage('de');
        $localization2 = new Localization();
        $localization2->setLanguage('en');

        $this->addLocalizationProvider([$localization1, $localization2]);
        $this->addLocalizationProvider([$localization2]);

        $localizations = $this->localizationManager->getLocalizations();

        $this->assertCount(2, $localizations);
        $this->assertContains($localization1, $localizations);
        $this->assertContains($localization2, $localizations);
    }

    private function addLocalizationProvider($localizations)
    {
        $localizationProvider1 = $this->prophesize(
            'Sulu\Component\Localization\Provider\LocalizationProviderInterface'
        );
        $localizationProvider1->getAllLocalizations()->willReturn($localizations);

        $this->localizationManager->addLocalizationProvider($localizationProvider1->reveal());
    }
}
