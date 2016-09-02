<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Provider;

use Sulu\Component\Localization\Localization;

/**
 * Basic localization provider.
 */
class LocalizationProvider implements LocalizationProviderInterface
{
    /**
     * @var array
     */
    private $locales;

    /**
     * @param array $locales
     */
    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLocalizations()
    {
        $result = [];
        foreach ($this->locales as $locale) {
            $result[] = $this->parse($locale);
        }

        return $result;
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
        $parts = explode('_', $locale);

        $localization = new Localization();
        $localization->setLanguage($parts[0]);
        if (count($parts) > 1) {
            $localization->setCountry($parts[1]);
        }

        return $localization;
    }
}
