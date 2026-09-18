<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Route;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceCollection;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Infrastructure\Sulu\Route\WebspaceSiteLocaleProvider;
use Sulu\Route\Application\LanguageSwitcher\SiteLocaleProviderInterface;

#[CoversClass(WebspaceSiteLocaleProvider::class)]
class WebspaceSiteLocaleProviderTest extends TestCase
{
    public function testProvideReturnsTheLocalizationsOfTheWebspace(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');
        $en = new Localization('en');
        $enUs = new Localization('en', 'us');
        $en->addChild($enUs);
        $webspace->addLocalization($en);
        $webspace->addLocalization(new Localization('de'));

        $provider = new WebspaceSiteLocaleProvider($this->createWebspaceManager($webspace), $this->createInnerProvider());

        $this->assertSame(['en', 'en_us', 'de'], $provider->provide('sulu_io'));
    }

    public function testProvideFallsBackToTheInnerProviderForUnknownSites(): void
    {
        $provider = new WebspaceSiteLocaleProvider($this->createWebspaceManager(), $this->createInnerProvider());

        $this->assertSame(['fr'], $provider->provide('unknown'));
    }

    private function createWebspaceManager(?Webspace $webspace = null): WebspaceManagerInterface
    {
        $webspaceManager = $this->createMock(WebspaceManagerInterface::class);
        $webspaceManager->method('getWebspaceCollection')
            ->willReturn(new WebspaceCollection(null !== $webspace ? [$webspace->getKey() => $webspace] : []));

        return $webspaceManager;
    }

    private function createInnerProvider(): SiteLocaleProviderInterface
    {
        return new class() implements SiteLocaleProviderInterface {
            public function provide(string $site): array
            {
                return ['fr'];
            }
        };
    }
}
