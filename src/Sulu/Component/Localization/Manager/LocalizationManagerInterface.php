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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Provider\LocalizationProviderInterface;

/**
 * Interface for the management of localizations, the implementing class will manage the localizations based
 * on LocalizationProviders, which can be registered via the DIC.
 */
interface LocalizationManagerInterface
{
    /**
     * Returns all the localizations, which are available in this system.
     *
     * @return Localization[]
     */
    public function getLocalizations();

    /**
     * Adds another LocalizationProvider to the manager.
     *
     * @param LocalizationProviderInterface $localizationProvider
     */
    public function addLocalizationProvider(LocalizationProviderInterface $localizationProvider);
}
