<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Locale;

use Sulu\Component\Localization\Localization;

/**
 * Interface to provide default locale.
 */
interface DefaultLocaleProviderInterface
{
    /**
     * Return default locale.
     *
     * @return Localization
     */
    public function getDefaultLocale();
}
