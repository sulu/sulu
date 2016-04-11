<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

/**
 * Find best localization.
 */
interface LocalizationFinderInterface
{
    /**
     * @param string $webspaceName
     * @param string[] $availableLocales
     * @param string $locale
     *
     * @return string
     */
    public function findAvailableLocale($webspaceName, array $availableLocales, $locale);
}
