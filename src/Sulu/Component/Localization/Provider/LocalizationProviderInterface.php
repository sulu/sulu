<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * Returns all the localizations offered for the system by this class.
     *
     * @return Localization[]
     */
    public function getAllLocalizations();
}
