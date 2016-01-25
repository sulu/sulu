<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Generator;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Url\ReplacerFactoryInterface;

/**
 * Generates urls for custom-urls.
 */
class Generator implements GeneratorInterface
{
    const PREFIX_REGEX = '/^([^\/]*)(\*)(.*)$/';
    const POSTFIX_REGEX = '/^.*\/.*\*.*$/';

    /**
     * @var ReplacerFactoryInterface
     */
    private $urlReplacerFactory;

    public function __construct(ReplacerFactoryInterface $urlReplacerFactory)
    {
        $this->urlReplacerFactory = $urlReplacerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($baseDomain, $domainParts, Localization $locale = null)
    {
        $domain = $baseDomain;
        if (preg_match(self::PREFIX_REGEX, $baseDomain)) {
            $domain = preg_replace('/\*/', $domainParts['prefix'], $domain, 1);
        }

        if (!preg_match(self::POSTFIX_REGEX, $baseDomain)) {
            $domain = rtrim($domain, '/') . '/*';
        }

        foreach ($domainParts['suffix'] as $suffix) {
            $domain = preg_replace('/\*/', $suffix, $domain, 1);
        }

        if ($locale) {
            $domain = $this->localizeDomain($domain, $locale);
        }

        return rtrim($domain, '/');
    }

    /**
     * Localize given domain.
     *
     * @param string $domain
     * @param Localization $locale
     *
     * @return string[]
     */
    protected function localizeDomain($domain, Localization $locale)
    {
        $replacer = $this->urlReplacerFactory->create($domain);

        if (!$replacer->hasLocalizationReplacer() && !$replacer->hasLanguageReplacer()) {
            $replacer->appendLocalizationReplacer();
        }

        return $replacer
            ->replaceLanguage($locale->getLanguage())
            ->replaceCountry($locale->getCountry())
            ->replaceLocalization($locale->getLocalization())
            ->cleanup()
            ->get();
    }
}
