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

    /**
     * {@inheritdoc}
     */
    public function getWebspaceKey()
    {
        return $this->webspaceKey;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebspaceKey($webspaceKey)
    {
        $this->webspaceKey = $webspaceKey;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocalization()
    {
        return $this->defaultLocalization;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultLocalization($defaultLocalization)
    {
        $this->defaultLocalization = $defaultLocalization;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSitemap()
    {
        return $this->sitemap;
    }

    /**
     * {@inheritdoc}
     */
    public function setSitemap($sitemap)
    {
        $this->sitemap = $sitemap;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalizations()
    {
        return $this->localizations;
    }

    /**
     * {@inheritdoc}
     */
    public function setLocalizations($localizations)
    {
        $this->localizations = $localizations;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addLocalization($localization)
    {
        $this->localizations[$localization] = $localization;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeLocalization($localization)
    {
        unset($this->localizations[$localization]);

        return $this;
    }
}
