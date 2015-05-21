<?php
/*
 * This file is part of the Sulu CMS.
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
     * @param string $url
     * @param string $webspaceKey
     * @param string $locale
     *
     * @return string
     */
    public function getContentPath($url, $webspaceKey = null, $locale = null);

    /**
     * Generates real root url.
     *
     * @param bool $full if TRUE the full url will be returned, if FALSE only the current prefix is returned
     *
     * @return string
     */
    public function getContentRootPath($full = false);
}
