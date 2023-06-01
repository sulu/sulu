<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

/**
 * Helper class to replace localization information in urls.
 */
class Replacer implements ReplacerInterface
{
    private $replacers = [
        self::REPLACER_LANGUAGE,
        self::REPLACER_COUNTRY,
        self::REPLACER_LOCALIZATION,
        self::REPLACER_SEGMENT,
        self::REPLACER_HOST,
    ];

    public function hasLanguageReplacer($url)
    {
        return $this->hasReplacer($url, self::REPLACER_LANGUAGE);
    }

    public function replaceLanguage($url, $language)
    {
        return $this->replace($url, self::REPLACER_LANGUAGE, $language);
    }

    public function hasCountryReplacer($url)
    {
        return $this->hasReplacer($url, self::REPLACER_COUNTRY);
    }

    public function replaceCountry($url, $country)
    {
        return $this->replace($url, self::REPLACER_COUNTRY, $country);
    }

    public function hasLocalizationReplacer($url)
    {
        return $this->hasReplacer($url, self::REPLACER_LOCALIZATION);
    }

    public function replaceLocalization($url, $localization)
    {
        return $this->replace($url, self::REPLACER_LOCALIZATION, $localization);
    }

    public function hasSegmentReplacer($url)
    {
        return $this->hasReplacer($url, self::REPLACER_SEGMENT);
    }

    public function replaceSegment($url, $segment)
    {
        return $this->replace($url, self::REPLACER_SEGMENT, $segment);
    }

    public function hasHostReplacer($url)
    {
        return $this->hasReplacer($url, self::REPLACER_HOST);
    }

    public function replaceHost($url, $host)
    {
        return $this->replace($url, self::REPLACER_HOST, $host);
    }

    public function replace($url, $replacer, $value)
    {
        if (!$url) {
            return '';
        }

        if (!$value) {
            $value = '';
        }

        return \str_replace($replacer, $value, $url);
    }

    public function cleanup($url, ?array $replacers = null)
    {
        if (!$replacers) {
            $replacers = $this->replacers;
        }

        foreach ($replacers as $replacer) {
            $url = $this->replace($url, $replacer, '');
        }

        $url = \ltrim($url, '.');
        $url = \rtrim($url, '/');

        return \str_replace('//', '/', $url);
    }

    public function appendLocalizationReplacer($url)
    {
        return \rtrim($url, '/') . '/' . self::REPLACER_LOCALIZATION;
    }

    /**
     * Returns true if given replacer exists.
     *
     * @param string $url
     * @param string $replacer
     *
     * @return $this
     */
    protected function hasReplacer($url, $replacer)
    {
        return \strpos($url, $replacer) > -1;
    }
}
