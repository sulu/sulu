<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Provider;

use Sulu\Component\Localization\Localization;

class LocalizationProvider implements LocalizationProviderInterface
{
    /**
     * @var string[]
     */
    private $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    public function getAllLocalizations(): array
    {
        $result = [];
        foreach ($this->locales as $locale) {
            $result[] = $this->parse($locale);
        }

        return $result;
    }

    public function getAllLocales(): array
    {
        return $this->locales;
    }

    /**
     * Converts locale string to localization object.
     *
     * @param string $locale E.g. de_at or de
     *
     * @return Localization
     */
    private function parse($locale)
    {
        $parts = \explode('_', $locale);

        $localization = new Localization($parts[0]);
        if (\count($parts) > 1) {
            $localization->setCountry($parts[1]);
        }

        return $localization;
    }
}
