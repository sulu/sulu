<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Markup\Link;

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
     * Returns all providers.
     *
     * @return LinkProviderInterface[]
     */
    public function getAllProviders();

    /**
     * Returns true if provider exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProvider($name);
}
