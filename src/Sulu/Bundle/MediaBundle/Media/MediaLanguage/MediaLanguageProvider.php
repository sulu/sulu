<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\MediaLanguage;

use Sulu\Bundle\MediaBundle\Infrastructure\Sulu\ListBuilder\MediaLanguageFilterType;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Intl\Locales;

/**
 * Selectable media languages: the configured list, or the content locales by default.
 *
 * @internal
 */
class MediaLanguageProvider
{
    /**
     * @param string[] $configuredLanguages
     */
    public function __construct(
        private array $configuredLanguages,
        private LocalizationManagerInterface $localizationManager,
    ) {
    }

    /**
     * @return array<array{name: string, title: string}>
     */
    public function getValues(string $locale): array
    {
        $values = [];
        foreach ($this->getLanguageCodes() as $code) {
            $values[] = ['name' => $code, 'title' => $this->getLanguageName($code, $locale)];
        }

        return $values;
    }

    /**
     * @return array<string, string>
     */
    public function getFilterOptions(string $locale): array
    {
        $options = [];
        foreach ($this->getLanguageCodes() as $code) {
            $options[$code] = $this->getLanguageName($code, $locale);
        }
        $options[MediaLanguageFilterType::NONE_VALUE] = 'sulu_media.media_language_none';

        return $options;
    }

    /**
     * @return string[]
     */
    private function getLanguageCodes(): array
    {
        if ([] !== $this->configuredLanguages) {
            return \array_values(\array_unique($this->configuredLanguages));
        }

        return \array_values(\array_unique($this->localizationManager->getLocales()));
    }

    private function getLanguageName(string $code, string $displayLocale): string
    {
        $parts = \explode('_', \str_replace('-', '_', $code));
        $language = \strtolower($parts[0]);
        $locale = isset($parts[1]) ? $language . '_' . \strtoupper($parts[1]) : $language;

        if (Locales::exists($locale)) {
            return Locales::getName($locale, $displayLocale);
        }

        return Languages::exists($language) ? Languages::getName($language, $displayLocale) : $code;
    }
}
