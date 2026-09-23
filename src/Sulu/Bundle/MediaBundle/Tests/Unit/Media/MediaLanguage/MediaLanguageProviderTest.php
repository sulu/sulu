<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\MediaLanguage;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Media\MediaLanguage\MediaLanguageProvider;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

class MediaLanguageProviderTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<LocalizationManagerInterface>
     */
    private ObjectProphecy $localizationManager;

    protected function setUp(): void
    {
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
    }

    public function testGetLanguageCodesUsesConfiguredLanguages(): void
    {
        $this->localizationManager->getLocales()->shouldNotBeCalled();
        $provider = new MediaLanguageProvider(['fr', 'de', 'fr'], $this->localizationManager->reveal());

        self::assertSame(['fr', 'de'], $provider->getLanguageCodes());
    }

    public function testGetLanguageCodesFallsBackToContentLocales(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en', 'de']);
        $provider = new MediaLanguageProvider([], $this->localizationManager->reveal());

        self::assertSame(['en', 'de'], $provider->getLanguageCodes());
    }

    public function testGetLanguageNamesLocalizesTheDisplayName(): void
    {
        $provider = new MediaLanguageProvider(['de', 'en'], $this->localizationManager->reveal());

        self::assertSame(['de' => 'German', 'en' => 'English'], $provider->getLanguageNames('en'));
    }

    public function testGetLanguageNameReducesARegionLocaleToItsLanguage(): void
    {
        $provider = new MediaLanguageProvider([], $this->localizationManager->reveal());

        self::assertSame('English', $provider->getLanguageName('en_US', 'en'));
    }

    public function testGetLanguageNameReturnsTheCodeWhenUnknown(): void
    {
        $provider = new MediaLanguageProvider([], $this->localizationManager->reveal());

        self::assertSame('zz', $provider->getLanguageName('zz', 'en'));
    }
}
