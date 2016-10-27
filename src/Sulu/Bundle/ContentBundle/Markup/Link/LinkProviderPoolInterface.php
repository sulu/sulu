<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Markup\Link;

/**
 * Interface for link-provider-pool.
 */
interface LinkProviderPoolInterface
{
    /**
     * Returns provider by name.
     *
     * @param string $name
     *
     * @return LinkProviderInterface
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
     * @return LinkConfiguration[]
     */
    public function getConfiguration();
}
