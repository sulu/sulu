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
 * Defines the interface for providing the navigation data of the main area in Sulu. The provided data will be used to
 * create the tabs for the forms.
 */
interface ContentNavigationProviderInterface
{
    /**
     * Returns the navigation items this class provides.
     *
     * @param array $options
     *
     * @return ContentNavigationItem[]
     */
    public function getNavigationItems(array $options = []);
}
