<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Teaser\Provider;

use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;

/**
 * Interface for teaser-provider.
 */
interface TeaserProviderInterface
{
    /**
     * Return configuration for content-type.
     *
     * @return TeaserConfiguration
     */
    public function getConfiguration();

    /**
     * Returns teasers by ids.
     *
     * @param string $locale
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale);
}
