<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Class WebspaceSitemap
 * Store Webspace Information and Sitemap.
 */
class WebspaceSitemap implements WebspaceSitemapInterface
{
    /**
     * @var array
     */
    private $localizations;

    /**
     * @var string
     */
    private $defaultLocalization;

    /**
     * @var string
     */
    private $webspaceKey;

    /**
     * @var array
     */
    private $sitemap = [];

    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    public function setWebspaceKey($webspaceKey)
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    public function getDefaultLocalization()
    {
        return $this->defaultLocalization;
    }

    public function setDefaultLocalization($defaultLocalization)
    {
        $this->defaultLocalization = $defaultLocalization;

        return $this;
    }

    public function getSitemap()
    {
        return $this->sitemap;
    }

    public function setSitemap($sitemap)
    {
        $this->sitemap = $sitemap;

        return $this;
    }

    public function getLocalizations()
    {
        return $this->localizations;
    }

    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;

        return $this;
    }

    public function addLocalization($localization)
    {
        $this->localizations[$localization] = $localization;

        return $this;
    }

    public function removeLocalization($localization)
    {
        unset($this->localizations[$localization]);

        return $this;
    }
}
