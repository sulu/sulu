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

/**
 * Provides the URLs of a resource in every locale, as needed by a language switcher.
 */
interface LanguageSwitcherProviderInterface
{
    /**
     * @param string[] $publishedLocales The locales the resource is available in
     * @param bool $fallbackToStartpage Whether the locales of the site without a translation of the resource
     *                                  should point to the start page of the site in that locale
     *
     * @return array<string, array{
     *     url: string,
     *     locale: string,
     *     alternate: bool,
     * }> The entries indexed by locale, "alternate" being true when the URL points to the resource itself
     */
    public function provide(
        string $resourceKey,
        string $resourceId,
        string $site,
        array $publishedLocales,
        bool $fallbackToStartpage = false,
    ): array;
}
