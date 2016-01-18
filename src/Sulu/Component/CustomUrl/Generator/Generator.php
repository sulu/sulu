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
class Generator
{
    const PREFIX_REGEX = '/^([^\/]*)(\*)(.*)$/';
    const POSTFIX_REGEX = '/.*\/.*\*.*/';

    /**
     * @var ReplacerFactoryInterface
     */
    private $urlReplacerFactory;

    public function __construct(ReplacerFactoryInterface $urlReplacerFactory)
    {
        $this->urlReplacerFactory = $urlReplacerFactory;
    }

    /**
     * @param $baseDomain
     * @param $domainParts
     * @param Localization[] $locales
     *
     * @return array
     */
    public function generate($baseDomain, $domainParts, array $locales = null)
    {
        $domain = $baseDomain;
        if (preg_match(self::PREFIX_REGEX, $baseDomain)) {
            $domain = preg_replace(self::PREFIX_REGEX, '$1' . $domainParts['prefix'] . '$3', $domain);
        }

        if (!preg_match(self::POSTFIX_REGEX, $baseDomain)) {
            $domain = rtrim($domain, '/') . '/*';
        }

        foreach ($domainParts['postfix'] as $postfix) {
            $count = 1;
            $domain = str_replace('*', $postfix, $domain, $count);
        }

        if ($locales) {
            return array_map(
                function (Localization $localization) use ($domain) {
                    $replacer = $this->urlReplacerFactory->create($domain);

                    if (!$replacer->hasLocalizationReplacer() && !$replacer->hasLanguageReplacer()) {
                        $replacer->appendLocalizationReplacer();
                    }

                    return $replacer
                        ->replaceLanguage($localization->getLanguage())
                        ->replaceCountry($localization->getCountry())
                        ->replaceLocalization($localization->getLocalization())
                        ->cleanup()
                        ->get();
                },
                $locales
            );
        }

        return [$domain];
    }
}
