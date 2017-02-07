<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

/**
 * generates frontend navigation.
 */
interface NavigationMapperInterface
{
    /**
     * returns navigation for given parent.
     *
     * @param string $parent uuid of parent node
     * @param $webspaceKey
     * @param $locale
     * @param int         $depth
     * @param bool        $flat
     * @param string|null $context
     * @param bool        $loadExcerpt
     *
     * @return NavigationItem[]
     */
    public function getNavigation(
        $parent,
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false
    );

    /**
     * returns navigation from root.
     *
     * @param string      $webspaceKey
     * @param string      $locale
     * @param int         $depth
     * @param bool        $flat
     * @param string|null $context
     * @param bool        $loadExcerpt
     *
     * @return NavigationItem[]
     */
    public function getRootNavigation(
        $webspaceKey,
        $locale,
        $depth = 1,
        $flat = false,
        $context = null,
        $loadExcerpt = false
    );

    /**
     * returns a breadcrumb navigation for given content-uuid.
     *
     * @param string $uuid
     * @param string $webspace
     * @param string $language
     *
     * @return NavigationItem[]
     */
    public function getBreadcrumb($uuid, $webspace, $language);
}
