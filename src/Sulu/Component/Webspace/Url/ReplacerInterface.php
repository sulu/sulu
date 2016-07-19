<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

/**
 * Interface for localization placeholder replacer.
 */
interface ReplacerInterface
{
    const REPLACER_LANGUAGE = '{language}';
    const REPLACER_COUNTRY = '{country}';
    const REPLACER_LOCALIZATION = '{localization}';
    const REPLACER_SEGMENT = '{segment}';
    const REPLACER_HOST = '{host}';

    /**
     * Returns true if language replacer exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasLanguageReplacer($url);

    /**
     * Replace language with given value.
     *
     * @param string $url
     * @param string $language
     *
     * @return string
     */
    public function replaceLanguage($url, $language);

    /**
     * Returns true if country replacer exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasCountryReplacer($url);

    /**
     * Replace country with given value.
     *
     * @param string $url
     * @param string $country
     *
     * @return string
     */
    public function replaceCountry($url, $country);

    /**
     * Returns true if localization replacer exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasLocalizationReplacer($url);

    /**
     * Replace localization with given value.
     *
     * @param string $url
     * @param string $localization
     *
     * @return string
     */
    public function replaceLocalization($url, $localization);

    /**
     * Returns true if segment replacer exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasSegmentReplacer($url);

    /**
     * Replace segment with given value.
     *
     * @param string $url
     * @param string $segment
     *
     * @return string
     */
    public function replaceSegment($url, $segment);

    /**
     * Returns true if host replacer exists.
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasHostReplacer($url);

    /**
     * Replace host with given value.
     *
     * @param string $url
     * @param string $host
     *
     * @return string
     */
    public function replaceHost($url, $host);

    /**
     * Replace replacer with given value.
     *
     * @param string $url
     * @param string $replacer
     * @param string $value
     *
     * @return string
     */
    public function replace($url, $replacer, $value);

    /**
     * Removes all replacers.
     *
     * @param string $url
     * @param array $replacers
     *
     * @return string
     */
    public function cleanup($url, array $replacers = null);

    /**
     * Appends localization replacer to url.
     *
     * @return string
     */
    public function appendLocalizationReplacer($url);
}
