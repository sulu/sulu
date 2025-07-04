<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Manager;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Provider\LocalizationProviderInterface;

/**
 * Manages all the localizations available in the system.
 */
class LocalizationManager implements LocalizationManagerInterface
{
    /**
     * Contains all the registered LocalizationProviders.
     *
     * @var LocalizationProviderInterface[]
     */
    private $localizationProviders = [];

    /**
     * @param array<LocalizationProviderInterface> $localizationProviders
     */
    public function __construct(iterable $localizationProviders = [])
    {
        $this->localizationProviders = [...$localizationProviders];
    }

    public function getLocalizations(): array
    {
        $localizations = [];

        foreach ($this->localizationProviders as $localizationProvider) {
            foreach ($localizationProvider->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocale()] = $localization;
            }
        }

        return $localizations;
    }

    public function getLocales(): array
    {
        return \array_values(
            \array_map(
                function(Localization $localization) {
                    return $localization->getLocale();
                },
                $this->getLocalizations()
            )
        );
    }

    /**
     * @deprecated Use the constructor instead
     */
    public function addLocalizationProvider(LocalizationProviderInterface $localizationProvider)
    {
        $this->localizationProviders[] = $localizationProvider;
    }
}
