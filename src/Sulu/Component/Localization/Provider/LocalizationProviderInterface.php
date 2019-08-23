<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Localization\Provider;

use Sulu\Component\Localization\Localization;

/**
 * Defines the method to return the localizations offered for the system.
 */
interface LocalizationProviderInterface
{
    /**
     * @return Localization[]
     */
    public function getAllLocalizations(): array;

    /**
     * @return string[]
     */
    public function getAllLocales(): array;
}
