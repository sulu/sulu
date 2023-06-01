<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Sitemap;

use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides twig functions for sitemap.
 */
class SitemapTwigExtension extends AbstractExtension implements SitemapTwigExtensionInterface
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
        ?RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->environment = $environment;
        $this->sitemapGenerator = $sitemapGenerator;
        $this->webspaceManager = $webspaceManager;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_sitemap_url', [$this, 'sitemapUrlFunction']),
            new TwigFunction('sulu_sitemap', [$this, 'sitemapFunction']),
        ];
    }

    public function sitemapUrlFunction($url, $locale = null, $webspaceKey = null)
    {
        if (null === $webspaceKey) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }

        if (null === $locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        return $this->webspaceManager->findUrlByResourceLocator(
            $url,
            $this->environment,
            $locale,
            $webspaceKey
        );
    }

    public function sitemapFunction($locale = null, $webspaceKey = null)
    {
        if (null === $webspaceKey) {
            $webspaceKey = $this->requestAnalyzer->getWebspace()->getKey();
        }

        if (null === $locale) {
            $locale = $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        }

        return $this->sitemapGenerator->generate($webspaceKey, $locale)->getSitemap();
    }
}
