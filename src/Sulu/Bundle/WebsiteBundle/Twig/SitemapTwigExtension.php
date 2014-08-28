<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Provides twig functions for sitemap
 * @package Sulu\Bundle\WebsiteBundle\Twig
 */
class SitemapTwigExtension extends \Twig_Extension
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('sitemap_url', array($this, 'sitemapUrlFunction')),
            new \Twig_SimpleFunction('sitemap', array($this, 'sitemapFunction'))
        );
    }

    public function sitemapUrlFunction($url, $locale, $webspaceKey)
    {
        $portalUrls = $this->webspaceManager->findUrlsByResourceLocator($url, $this->environment, $locale, $webspaceKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_sitemap';
    }
}
