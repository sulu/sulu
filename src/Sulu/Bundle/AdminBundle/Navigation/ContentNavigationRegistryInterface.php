<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Navigation;

/**
 * Defines the interface for retrieving all the content navigation items for a specific alias.
 */
interface ContentNavigationRegistryInterface
{
    /**
     * Returns all the navigation items for the given alias.
     *
     * @param string $alias   The alias which specifies the returned group
     * @param array  $options An arbitrary list of options to pass to the navigation items
     *
     * @return ContentNavigationItem[]
     */
    public function getNavigationItems($alias, array $options = []);

    /**
     * Adds a content navigation provider to the given alias.
     *
     * @param string $alias The alias to which the given items should be added
     * @param string $id    The id of the content navigation provider
     */
    public function addContentNavigationProvider($alias, $id);
}
