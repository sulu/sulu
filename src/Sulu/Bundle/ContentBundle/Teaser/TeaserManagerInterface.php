<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser;

/**
 * Interface for teaser manager.
 */
interface TeaserManagerInterface
{
    /**
     * Returns teasers for given items.
     *
     * @param array $items
     * @param string $locale
     *
     * @return Teaser[]
     */
    public function find(array $items, $locale);
}
