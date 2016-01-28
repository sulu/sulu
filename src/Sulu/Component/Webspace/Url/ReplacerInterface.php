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

    /**
     * Returns true if language replacer exists.
     *
     * @return bool
     */
    public function hasLanguageReplacer();

    /**
     * Replace language with given value.
     *
     * @param string $language
     *
     * @return $this
     */
    public function replaceLanguage($language);

    /**
     * Returns true if country replacer exists.
     *
     * @return bool
     */
    public function hasCountryReplacer();

    /**
     * Replace country with given value.
     *
     * @param string $country
     *
     * @return $this
     */
    public function replaceCountry($country);

    /**
     * Returns true if localization replacer exists.
     *
     * @return bool
     */
    public function hasLocalizationReplacer();

    /**
     * Replace localization with given value.
     *
     * @param string $localization
     *
     * @return $this
     */
    public function replaceLocalization($localization);

    /**
     * Returns true if segment replacer exists.
     *
     * @return bool
     */
    public function hasSegmentReplacer();

    /**
     * Replace segment with given value.
     *
     * @param string $segment
     *
     * @return $this
     */
    public function replaceSegment($segment);

    /**
     * Removes all replacers.
     *
     * @return $this
     */
    public function cleanup();

    /**
     * Replace replacer with given value.
     *
     * @param string $replacer
     * @param string $value
     *
     * @return $this
     */
    public function replace($replacer, $value);

    /**
     * Appends localization replacer to url.
     *
     * @return $this
     */
    public function appendLocalizationReplacer();

    /**
     * Return url.
     *
     * @return string
     */
    public function get();
}
