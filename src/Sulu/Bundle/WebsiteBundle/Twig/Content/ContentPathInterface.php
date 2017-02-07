<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

/**
 * Provides the helper functions to generate path for content.
 */
interface ContentPathInterface
{
    /**
     * Generates real url for given content.
     *
     * @param string $route
     * @param string $webspaceKey
     * @param string $locale
     * @param string $domain
     * @param string $scheme
     *
     * @return string
     */
    public function getContentPath($route, $webspaceKey = null, $locale = null, $domain = null, $scheme = 'http');

    /**
     * Generates real root url.
     *
     * @param bool $full if TRUE the full url will be returned, if FALSE only the current prefix is returned
     *
     * @return string
     */
    public function getContentRootPath($full = false);
}
