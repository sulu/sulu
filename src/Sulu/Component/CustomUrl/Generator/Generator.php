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
use Sulu\Component\Webspace\Url\ReplacerInterface;

/**
 * Generates urls for custom-urls.
 */
class Generator implements GeneratorInterface
{
    const PREFIX_REGEX = '/^([^\/]*)(\*)(.*)$/';
    const POSTFIX_REGEX = '/^.*\/.*\*.*$/';

    /**
     * @var ReplacerInterface
     */
    private $urlReplacer;

    public function __construct(ReplacerInterface $urlReplacer)
    {
        $this->urlReplacer = $urlReplacer;
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

        $optionalSuffix = false;
        if (!preg_match(self::POSTFIX_REGEX, $baseDomain)) {
            $domain = rtrim($domain, '/') . '/*';
            $optionalSuffix = true;
        }

        foreach ($domainParts['suffix'] as $suffix) {
            if (empty($suffix)) {
                continue;
            }

            $domain = preg_replace('/\*/', $suffix, $domain, 1);
        }

        if ($optionalSuffix) {
            $domain = rtrim($domain, '/*');
        }

        if (strpos($domain, '*') > -1) {
            throw new MissingDomainPartException($baseDomain, $domainParts, $domain);
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
     * @return string
     */
    protected function localizeDomain($domain, Localization $locale)
    {
        if (!$this->urlReplacer->hasLocalizationReplacer($domain)
            && !$this->urlReplacer->hasLanguageReplacer($domain)
        ) {
            $domain = $this->urlReplacer->appendLocalizationReplacer($domain);
        }

        $domain = $this->urlReplacer->replaceLanguage($domain, $locale->getLanguage());
        $domain = $this->urlReplacer->replaceCountry($domain, $locale->getCountry());
        $domain = $this->urlReplacer->replaceLocalization($domain, $locale->getLocalization());

        return $this->urlReplacer->cleanup($domain);
    }
}
