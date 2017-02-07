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

/**
 * Generates domain for custom-urls.
 */
interface GeneratorInterface
{
    /**
     * Generates urls for given base-domain and domain-parts.
     * If locales are passed the urls will be localized by replacers after generation.
     *
     * @param $baseDomain
     * @param $domainParts
     * @param Localization $locale
     *
     * @return string
     */
    public function generate($baseDomain, $domainParts, Localization $locale = null);
}
