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
use Sulu\Component\Localization\Provider\LocalizationProviderInterface;

class LocalizationManagerTest extends TestCase
{
    use ProphecyTrait;

    public function testGetAllLocalizations(): void
    {
        $localization1 = new Localization('de');
        $localization2 = new Localization('en');
        $localization3 = new Localization('fr');

        $providers = [
            $this->createLocalizationProvider([$localization1, $localization2]),
            $this->createLocalizationProvider([$localization3]),
        ];

        $localizations = (new LocalizationManager($providers))->getLocalizations();

        $this->assertCount(3, $localizations);
        $this->assertContains($localization1, $localizations);
        $this->assertContains($localization2, $localizations);
        $this->assertContains($localization3, $localizations);
    }

    public function testGetAllLocalizationsWithSameLocalizations(): void
    {
        $localization1 = new Localization('de');
        $localization2 = new Localization('en');

        $providers = [
            $this->createLocalizationProvider([$localization1, $localization2]),
            $this->createLocalizationProvider([$localization2]),
        ];

        $localizations = (new LocalizationManager($providers))->getLocalizations();

        $this->assertCount(2, $localizations);
        $this->assertContains($localization1, $localizations);
        $this->assertContains($localization2, $localizations);
    }

    /**
     * @param array<Localization> $localizations
     */
    private function createLocalizationProvider(array $localizations): LocalizationProviderInterface
    {
        $localizationProvider = $this->prophesize(LocalizationProviderInterface::class);
        $localizationProvider->getAllLocalizations()->willReturn($localizations);

        return $localizationProvider->reveal();
    }
}
