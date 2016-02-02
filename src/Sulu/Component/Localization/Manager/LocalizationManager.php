<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Manager;

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
     * {@inheritdoc}
     */
    public function getLocalizations()
    {
        $localizations = [];

        foreach ($this->localizationProviders as $localizationProvider) {
            foreach ($localizationProvider->getAllLocalizations() as $localization) {
                $localizations[$localization->getLocalization()] = $localization;
            }
        }

        return $localizations;
    }

    /**
     * {@inheritdoc}
     */
    public function addLocalizationProvider(LocalizationProviderInterface $localizationProvider)
    {
        $this->localizationProviders[] = $localizationProvider;
    }
}
