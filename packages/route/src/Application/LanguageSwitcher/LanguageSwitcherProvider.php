<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Application\LanguageSwitcher;

use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Exception\WebspaceUrlNotFoundException;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * @final
 */
class LanguageSwitcherProvider implements LanguageSwitcherProviderInterface
{
    public function __construct(
        private readonly RouteRepositoryInterface $routeRepository,
        private readonly RouteGeneratorInterface $routeGenerator,
        private readonly SiteLocaleProviderInterface $siteLocaleProvider,
    ) {
    }

    public function provide(
        string $resourceKey,
        string $resourceId,
        string $site,
        array $publishedLocales,
        bool $fallbackToStartpage = false,
    ): array {
        $localizations = [];

        if ($fallbackToStartpage) {
            foreach ($this->siteLocaleProvider->provide($site) as $locale) {
                try {
                    $localizations[$locale] = [
                        'url' => $this->routeGenerator->generate('/', $locale, $site),
                        'locale' => $locale,
                        'alternate' => false,
                    ];
                } catch (WebspaceUrlNotFoundException) {
                    continue;
                }
            }
        }

        $routes = $this->routeRepository->findBy([
            'resourceKey' => $resourceKey,
            'resourceId' => $resourceId,
            'locales' => $publishedLocales,
        ]);

        foreach ($routes as $route) {
            $locale = $route->getLocale();

            try {
                $localizations[$locale] = [
                    'url' => $this->routeGenerator->generate($route->getSlug(), $locale, $site),
                    'locale' => $locale,
                    'alternate' => true,
                ];
            } catch (WebspaceUrlNotFoundException) {
                continue;
            }
        }

        return $localizations;
    }
}
