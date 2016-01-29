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
 * Helper class to replace localization information in urls.
 */
class Replacer implements ReplacerInterface
{
    private $replacers = [
        self::REPLACER_LANGUAGE,
        self::REPLACER_COUNTRY,
        self::REPLACER_LOCALIZATION,
        self::REPLACER_SEGMENT,
    ];

    /**
     * @var string
     */
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    public function hasLanguageReplacer()
    {
        return $this->hasReplacer(self::REPLACER_LANGUAGE);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceLanguage($language)
    {
        return $this->replace(self::REPLACER_LANGUAGE, $language);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCountryReplacer()
    {
        return $this->hasReplacer(self::REPLACER_COUNTRY);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceCountry($country)
    {
        return $this->replace(self::REPLACER_COUNTRY, $country);
    }

    /**
     * {@inheritdoc}
     */
    public function hasLocalizationReplacer()
    {
        return $this->hasReplacer(self::REPLACER_LOCALIZATION);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceLocalization($localization)
    {
        return $this->replace(self::REPLACER_LOCALIZATION, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSegmentReplacer()
    {
        return $this->hasReplacer(self::REPLACER_SEGMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function replaceSegment($segment)
    {
        return $this->replace(self::REPLACER_SEGMENT, $segment);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup()
    {
        foreach ($this->replacers as $replacer) {
            $this->replace($replacer, '');
        }

        $this->url = ltrim($this->url, '.');
        $this->url = rtrim($this->url, '/');
        $this->url = str_replace('//', '/', $this->url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function appendLocalizationReplacer()
    {
        $this->url = rtrim($this->url, '/') . '/' . self::REPLACER_LOCALIZATION;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->url;
    }

    /**
     * Returns true if given replacer exists.
     *
     * @param string $replacer
     *
     * @return $this
     */
    protected function hasReplacer($replacer)
    {
        return strpos($this->url, $replacer) > -1;
    }

    /**
     * Replace replacer with given value.
     *
     * @param string $replacer
     * @param string $value
     *
     * @return $this
     */
    protected function replace($replacer, $value)
    {
        $this->url = str_replace($replacer, $value, $this->url);

        return $this;
    }
}
