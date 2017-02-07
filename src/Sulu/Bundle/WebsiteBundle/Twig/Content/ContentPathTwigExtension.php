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

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * provides the content_path function to generate real urls for frontend.
 */
class ContentPathTwigExtension extends \Twig_Extension implements ContentPathInterface
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var string
     */
    private $environment;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        $environment,
        RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_content_path', [$this, 'getContentPath']),
            new \Twig_SimpleFunction('sulu_content_root_path', [$this, 'getContentRootPath']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getContentPath($route, $webspaceKey = null, $locale = null, $domain = null, $scheme = null, $withoutDomain = true)
    {
        // if the request analyzer null or a route is passed which is relative or inclusive a domain nothing should be
        // done (this is important for external-links in navigations)
        if (!$this->requestAnalyzer || strpos($route, '/') !== 0) {
            return $route;
        }

        $scheme = $scheme ?: $this->requestAnalyzer->getAttribute('scheme');
        $locale = $locale ?: $this->requestAnalyzer->getCurrentLocalization()->getLocale();
        $webspaceKey = $webspaceKey ?: $this->requestAnalyzer->getWebspace()->getKey();

        $url = null;
        $host = $this->requestAnalyzer->getAttribute('host');
        if (!$domain
            && $this->webspaceManager->findWebspaceByKey($webspaceKey)->hasDomain($host, $this->environment, $locale)
        ) {
            $domain = $host;
        }

        $url = $this->webspaceManager->findUrlByResourceLocator(
            $route,
            $this->environment,
            $locale,
            $webspaceKey,
            $domain,
            $scheme
        );

        if (!$withoutDomain && !$url) {
            $url = $this->webspaceManager->findUrlByResourceLocator(
                $route,
                $this->environment,
                $locale,
                $webspaceKey,
                null,
                $scheme
            );
        }

        return $url ?: $route;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentRootPath($full = false)
    {
        return $this->getContentPath('/');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_website_content_path';
    }
}
