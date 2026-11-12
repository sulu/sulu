<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Route\Infrastructure\Symfony\HttpKernel;

use Sulu\Route\Application\LanguageSwitcher\SiteLocaleProviderInterface;

/**
 * Falls back to the locales enabled in the kernel ("framework.enabled_locales") for every site.
 *
 * @final
 */
class KernelSiteLocaleProvider implements SiteLocaleProviderInterface
{
    /**
     * @param string[] $enabledLocales
     */
    public function __construct(
        private readonly array $enabledLocales,
    ) {
    }

    public function provide(string $site): array
    {
        return $this->enabledLocales;
    }
}
