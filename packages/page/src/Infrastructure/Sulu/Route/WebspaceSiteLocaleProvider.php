<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Route;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Route\Application\LanguageSwitcher\SiteLocaleProviderInterface;

/**
 * Resolves the locales of a site from the localizations of the webspace with the same key.
 *
 * @final
 */
class WebspaceSiteLocaleProvider implements SiteLocaleProviderInterface
{
    public function __construct(
        private readonly WebspaceManagerInterface $webspaceManager,
        private readonly SiteLocaleProviderInterface $inner,
    ) {
    }

    public function provide(string $site): array
    {
        $webspace = $this->webspaceManager->getWebspaceCollection()->getWebspace($site);

        if (null === $webspace) {
            return $this->inner->provide($site);
        }

        return \array_map(
            static fn (Localization $localization): string => $localization->getLocale(Localization::UNDERSCORE),
            $webspace->getAllLocalizations(),
        );
    }
}
