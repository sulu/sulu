<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Tests\Unit\Application\LanguageSwitcher;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sulu\Route\Application\LanguageSwitcher\LanguageSwitcherProvider;
use Sulu\Route\Application\LanguageSwitcher\SiteLocaleProviderInterface;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Exception\WebspaceUrlNotFoundException;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

#[CoversClass(LanguageSwitcherProvider::class)]
class LanguageSwitcherProviderTest extends TestCase
{
    public function testProvideReturnsTheRoutesOfThePublishedLocales(): void
    {
        $routeRepository = $this->createMock(RouteRepositoryInterface::class);
        $routeRepository->expects($this->once())
            ->method('findBy')
            ->with(['resourceKey' => 'pages', 'resourceId' => '123', 'locales' => ['en', 'de']])
            ->willReturn([
                new Route('pages', '123', 'en', '/about-us', 'sulu_io'),
                new Route('pages', '123', 'de', '/ueber-uns', 'sulu_io'),
            ]);

        $provider = new LanguageSwitcherProvider($routeRepository, $this->createRouteGenerator(), $this->createSiteLocaleProvider(['en', 'de', 'fr']));

        $this->assertSame([
            'en' => ['url' => 'https://sulu.io/en/about-us', 'locale' => 'en', 'alternate' => true],
            'de' => ['url' => 'https://sulu.io/de/ueber-uns', 'locale' => 'de', 'alternate' => true],
        ], $provider->provide('pages', '123', 'sulu_io', ['en', 'de']));
    }

    public function testProvideFallsBackToTheStartpageForTheOtherLocalesOfTheSite(): void
    {
        $routeRepository = $this->createMock(RouteRepositoryInterface::class);
        $routeRepository->method('findBy')
            ->willReturn([new Route('pages', '123', 'en', '/about-us', 'sulu_io')]);

        $provider = new LanguageSwitcherProvider($routeRepository, $this->createRouteGenerator(), $this->createSiteLocaleProvider(['en', 'de', 'fr']));

        $this->assertSame([
            'en' => ['url' => 'https://sulu.io/en/about-us', 'locale' => 'en', 'alternate' => true],
            'de' => ['url' => 'https://sulu.io/de/', 'locale' => 'de', 'alternate' => false],
            'fr' => ['url' => 'https://sulu.io/fr/', 'locale' => 'fr', 'alternate' => false],
        ], $this->sortByLocale($provider->provide('pages', '123', 'sulu_io', ['en'], true)));
    }

    public function testProvideSkipsTheLocalesWithoutUrl(): void
    {
        $routeRepository = $this->createMock(RouteRepositoryInterface::class);
        $routeRepository->method('findBy')
            ->willReturn([
                new Route('pages', '123', 'en', '/about-us', 'sulu_io'),
                new Route('pages', '123', 'it', '/chi-siamo', 'sulu_io'),
            ]);

        $provider = new LanguageSwitcherProvider($routeRepository, $this->createRouteGenerator(['it']), $this->createSiteLocaleProvider(['en', 'it']));

        $this->assertSame([
            'en' => ['url' => 'https://sulu.io/en/about-us', 'locale' => 'en', 'alternate' => true],
        ], $provider->provide('pages', '123', 'sulu_io', ['en', 'it'], true));
    }

    public function testProvideOnlyReturnsTheStartpagesWhenTheResourceHasNoRoute(): void
    {
        $routeRepository = $this->createMock(RouteRepositoryInterface::class);
        $routeRepository->method('findBy')->willReturn([]);

        $provider = new LanguageSwitcherProvider($routeRepository, $this->createRouteGenerator(), $this->createSiteLocaleProvider(['en']));

        $this->assertSame([], $provider->provide('pages', '123', 'sulu_io', []));
        $this->assertSame([
            'en' => ['url' => 'https://sulu.io/en/', 'locale' => 'en', 'alternate' => false],
        ], $provider->provide('pages', '123', 'sulu_io', [], true));
    }

    /**
     * @param string[] $localesWithoutUrl
     */
    private function createRouteGenerator(array $localesWithoutUrl = []): RouteGeneratorInterface
    {
        $routeGenerator = $this->createMock(RouteGeneratorInterface::class);
        $routeGenerator->method('generate')
            ->willReturnCallback(static function(string $slug, ?string $locale, ?string $site) use ($localesWithoutUrl): string {
                if (\in_array($locale, $localesWithoutUrl, true)) {
                    throw new WebspaceUrlNotFoundException($slug, (string) $locale, (string) $site);
                }

                return 'https://sulu.io/' . $locale . $slug;
            });

        return $routeGenerator;
    }

    /**
     * @param string[] $locales
     */
    private function createSiteLocaleProvider(array $locales): SiteLocaleProviderInterface
    {
        return new class($locales) implements SiteLocaleProviderInterface {
            /**
             * @param string[] $locales
             */
            public function __construct(private readonly array $locales)
            {
            }

            public function provide(string $site): array
            {
                return 'sulu_io' === $site ? $this->locales : [];
            }
        };
    }

    /**
     * @param array<string, mixed> $localizations
     *
     * @return array<string, mixed>
     */
    private function sortByLocale(array $localizations): array
    {
        \ksort($localizations);
        $sorted = [];
        foreach (['en', 'de', 'fr'] as $locale) {
            if (isset($localizations[$locale])) {
                $sorted[$locale] = $localizations[$locale];
            }
        }

        return $sorted;
    }
}
