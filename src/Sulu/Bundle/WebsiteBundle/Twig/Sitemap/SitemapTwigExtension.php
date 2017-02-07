<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Sitemap;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Provides twig functions for sitemap.
 */
class SitemapTwigExtension extends \Twig_Extension implements SitemapTwigExtensionInterface
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SitemapGeneratorInterface
     */
    private $sitemapGenerator;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        SitemapGeneratorInterface $sitemapGenerator,
        WebspaceManagerInterface $webspaceManager,
        $environment,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->environment = $environment;
        $this->sitemapGenerator = $sitemapGenerator;
        $this->webspaceManager = $webspaceManager;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_sitemap_url', [$this, 'sitemapUrlFunction']),
            new \Twig_SimpleFunction('sulu_sitemap', [$this, 'sitemapFunction']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sitemapUrlFunction($url, $locale = null, $webspaceKey = null)
    {
        if ($webspaceKey === null) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }

        if ($locale === null) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();
        }

        return $this->webspaceManager->findUrlByResourceLocator(
            $url,
            $this->environment,
            $locale,
            $webspaceKey
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sitemapFunction($locale = null, $webspaceKey = null)
    {
        if ($webspaceKey === null) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }

        if ($locale === null) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocalization();
        }

        return $this->sitemapGenerator->generate($webspaceKey, $locale)->getSitemap();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_sitemap';
    }
}
