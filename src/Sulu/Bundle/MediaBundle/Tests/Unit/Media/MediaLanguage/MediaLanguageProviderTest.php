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
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\MediaLanguageFilterType;
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

    public function testGetValuesUsesConfiguredLanguages(): void
    {
        $this->localizationManager->getLocales()->shouldNotBeCalled();
        $provider = new MediaLanguageProvider(['fr', 'de', 'fr'], $this->localizationManager->reveal());

        self::assertSame(
            [['name' => 'fr', 'title' => 'French'], ['name' => 'de', 'title' => 'German']],
            $provider->getValues('en')
        );
    }

    public function testGetValuesFallsBackToContentLocales(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en', 'de']);
        $provider = new MediaLanguageProvider([], $this->localizationManager->reveal());

        self::assertSame(
            [['name' => 'en', 'title' => 'Englisch'], ['name' => 'de', 'title' => 'Deutsch']],
            $provider->getValues('de')
        );
    }

    public function testGetValuesKeepsRegionLocalesApart(): void
    {
        $provider = new MediaLanguageProvider(['de_at', 'de', 'en-US'], $this->localizationManager->reveal());

        self::assertSame(
            [
                ['name' => 'de_at', 'title' => 'German (Austria)'],
                ['name' => 'de', 'title' => 'German'],
                ['name' => 'en-US', 'title' => 'English (United States)'],
            ],
            $provider->getValues('en')
        );
    }

    public function testGetValuesReturnsTheCodeWhenUnknown(): void
    {
        $provider = new MediaLanguageProvider(['zz'], $this->localizationManager->reveal());

        self::assertSame([['name' => 'zz', 'title' => 'zz']], $provider->getValues('en'));
    }

    public function testGetFilterOptionsAddsTheNoneOption(): void
    {
        $provider = new MediaLanguageProvider(['de'], $this->localizationManager->reveal());

        self::assertSame(
            ['de' => 'German', MediaLanguageFilterType::NONE_VALUE => 'sulu_media.media_language_none'],
            $provider->getFilterOptions('en')
        );
    }
}
