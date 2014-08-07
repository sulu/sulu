<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

/**
 * generates frontend navigation
 * @package Sulu\Bundle\WebsiteBundle\Navigation
 */
interface NavigationMapperInterface
{
    /**
     * returns navigation for given parent
     * @param string $parent uuid of parent node
     * @param $webspace
     * @param $language
     * @param int $depth
     * @param string|null $context
     * @return NavigationItem[]
     */
    public function getNavigation($parent, $webspace, $language, $depth = 1, $context = null);

    /**
     * returns navigation from root
     * @param string $webspace
     * @param string $language
     * @param int $depth
     * @param string|null $context
     * @return NavigationItem[]
     */
    public function getMainNavigation($webspace, $language, $depth = 1, $context = null);

    /**
     * returns a breadcrumb navigation for given content-uuid
     * @param string $uuid
     * @param string $webspace
     * @param string $language
     * @return NavigationItem[]
     */
    public function getBreadcrumb($uuid, $webspace, $language);
} 
