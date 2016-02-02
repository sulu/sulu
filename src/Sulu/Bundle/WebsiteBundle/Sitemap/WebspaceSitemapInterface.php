<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Interface WebspaceSitemapInterface
 * Store Webspace Information for Sitemap generation.
 */
interface WebspaceSitemapInterface
{
    /**
     * Get the webspaceKey.
     *
     * @return string
     */
    public function getWebspaceKey();

    /**
     * Set the webspaceKey.
     *
     * @param string $webspaceKey
     *
     * @return $this
     */
    public function setWebspaceKey($webspaceKey);

    /**
     * Get Webspace Default Localization.
     *
     * @return string
     */
    public function getDefaultLocalization();

    /**
     * Set Webspace Default Localization.
     *
     * @param string $defaultLocalization
     *
     * @return $this
     */
    public function setDefaultLocalization($defaultLocalization);

    /**
     * Get the generated Sitemap.
     *
     * @return array
     */
    public function getSitemap();

    /**
     * Set the generated Sitemap.
     *
     * @param array $sitemap
     *
     * @return $this
     */
    public function setSitemap($sitemap);

    /**
     * Get the webspace localizations.
     *
     * @return string[]
     */
    public function getLocalizations();

    /**
     * Set the webspace localizations.
     *
     * @param string[] $localizations
     *
     * @return $this
     */
    public function setLocalizations($localizations);

    /**
     * Add a webspace localization.
     *
     * @param string $localization
     *
     * @return $this
     */
    public function addLocalization($localization);

    /**
     * Remove a webspace localization.
     *
     * @param string $localization
     *
     * @return $this
     */
    public function removeLocalization($localization);
}
