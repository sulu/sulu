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

use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Symfony\Component\Intl\Languages;

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
     * @return string[]
     */
    public function getLanguageCodes(): array
    {
        if ([] !== $this->configuredLanguages) {
            return \array_values(\array_unique($this->configuredLanguages));
        }

        return \array_values(\array_unique($this->localizationManager->getLocales()));
    }

    /**
     * @return array<string, string> language code mapped to its localized display name
     */
    public function getLanguageNames(string $displayLocale): array
    {
        $names = [];
        foreach ($this->getLanguageCodes() as $code) {
            $names[$code] = $this->getLanguageName($code, $displayLocale);
        }

        return $names;
    }

    public function getLanguageName(string $code, string $displayLocale): string
    {
        $normalized = \str_replace('-', '_', $code);

        foreach ([$normalized, \explode('_', $normalized)[0]] as $candidate) {
            if (Languages::exists($candidate)) {
                return Languages::getName($candidate, $displayLocale);
            }
        }

        return $code;
    }
}
