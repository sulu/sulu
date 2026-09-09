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
 * Provides the locales a site (e.g. a webspace) is available in.
 */
interface SiteLocaleProviderInterface
{
    /**
     * @return string[]
     */
    public function provide(string $site): array;
}
