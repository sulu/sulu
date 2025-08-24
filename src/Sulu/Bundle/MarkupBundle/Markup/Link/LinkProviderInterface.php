<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

/**
 * Provides links.
 */
interface LinkProviderInterface
{
    /**
     * Return configuration for content-type.
     *
     * @return LinkConfiguration|LinkConfigurationBuilder|null
     */
    public function getConfiguration();

    /**
     * Load given items identified by the given hrefs.
     *
     * @param string[] $hrefs
     * @param string $locale
     * @param bool $published
     *
     * @return LinkItem[]
     */
    public function preload(array $hrefs, $locale, $published = true);
}
