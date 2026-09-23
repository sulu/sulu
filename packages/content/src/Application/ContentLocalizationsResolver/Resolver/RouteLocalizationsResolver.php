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

namespace Sulu\Content\Application\ContentLocalizationsResolver\Resolver;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentLocalizationsResolver\ContentLocalizationsResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\RoutableInterface;
use Sulu\Content\Infrastructure\Sulu\Route\Exception\WebspaceUrlNotFoundException;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;

/**
 * The default for content without a resolver of its own: every webspace locale links to the start
 * page, unless the content has a route in that locale.
 *
 * @final
 */
class RouteLocalizationsResolver implements ContentLocalizationsResolverInterface
{
    public function __construct(
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly RouteRepositoryInterface $routeRepository,
        private readonly RouteGeneratorInterface $routeGenerator,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, string $webspaceKey): array
    {
        if (!$dimensionContent instanceof RoutableInterface) {
            return [];
        }

        $webspaceLocales = $this->webspaceManager
            ->getWebspaceCollection()
            ->getWebspace($webspaceKey)
            ?->getAllLocalizations() ?? [];

        $localizations = [];
        foreach ($webspaceLocales as $webspaceLocale) {
            $locale = $webspaceLocale->getLocale(Localization::UNDERSCORE);
            try {
                $localizations[$locale] = [
                    'url' => $this->routeGenerator->generate('/', $locale, $webspaceKey),
                    'locale' => $locale,
                    'alternate' => false,
                ];
            } catch (WebspaceUrlNotFoundException) {
                continue;
            }
        }

        $routes = $this->routeRepository->findBy([
            'resourceKey' => $dimensionContent::getResourceKey(),
            'resourceId' => (string) $dimensionContent->getResource()->getId(),
            'locales' => $dimensionContent->getAvailableLocales() ?? [],
        ]);

        foreach ($routes as $route) {
            $locale = $route->getLocale();
            try {
                $localizations[$locale] = [
                    'url' => $this->routeGenerator->generate($route->getSlug(), $route->getLocale(), $webspaceKey),
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
