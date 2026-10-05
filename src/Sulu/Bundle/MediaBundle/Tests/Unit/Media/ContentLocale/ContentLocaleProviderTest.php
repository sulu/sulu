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

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\ContentLocale;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\ContentLocaleFilterType;
use Sulu\Bundle\MediaBundle\Media\ContentLocale\ContentLocaleProvider;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;

class ContentLocaleProviderTest extends TestCase
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

    public function testGetValuesUsesConfiguredLocales(): void
    {
        $this->localizationManager->getLocales()->shouldNotBeCalled();
        $provider = new ContentLocaleProvider(['fr', 'de', 'fr'], $this->localizationManager->reveal());

        self::assertSame(
            [['name' => 'fr', 'title' => 'French'], ['name' => 'de', 'title' => 'German']],
            $provider->getValues('en')
        );
    }

    public function testGetValuesFallsBackToContentLocales(): void
    {
        $this->localizationManager->getLocales()->willReturn(['en', 'de']);
        $provider = new ContentLocaleProvider([], $this->localizationManager->reveal());

        self::assertSame(
            [['name' => 'en', 'title' => 'Englisch'], ['name' => 'de', 'title' => 'Deutsch']],
            $provider->getValues('de')
        );
    }

    public function testGetValuesKeepsRegionLocalesApart(): void
    {
        $provider = new ContentLocaleProvider(['de_at', 'de', 'en-US'], $this->localizationManager->reveal());

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
        $provider = new ContentLocaleProvider(['zz'], $this->localizationManager->reveal());

        self::assertSame([['name' => 'zz', 'title' => 'zz']], $provider->getValues('en'));
    }

    public function testGetFilterOptionsAddsTheNoneOption(): void
    {
        $provider = new ContentLocaleProvider(['de'], $this->localizationManager->reveal());

        self::assertSame(
            ['de' => 'German', ContentLocaleFilterType::NONE_VALUE => 'sulu_media.content_locale_none'],
            $provider->getFilterOptions('en')
        );
    }
}
