<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser\Provider;

use Sulu\Bundle\ContentBundle\Teaser\Configuration\TeaserConfiguration;

/**
 * Interface for teaser-provider-pool.
 */
interface TeaserProviderPoolInterface
{
    /**
     * Returns provider by name.
     *
     * @param string $name
     *
     * @return TeaserProviderInterface
     *
     * @throws ProviderNotFoundException
     */
    public function getProvider($name);

    /**
     * Returns true if provider exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider($name);

    /**
     * Returns configuration for content-type.
     *
     * @return TeaserConfiguration[]
     */
    public function getConfiguration();
}
