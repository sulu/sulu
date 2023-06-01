<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig\Content;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * provides the content_path function to generate real urls for frontend.
 */
class ContentPathTwigExtension extends AbstractExtension implements ContentPathInterface
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
        ?RequestAnalyzerInterface $requestAnalyzer = null
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->requestAnalyzer = $requestAnalyzer;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('sulu_content_path', [$this, 'getContentPath']),
            new TwigFunction('sulu_content_root_path', [$this, 'getContentRootPath']),
        ];
    }

    public function getContentPath($route, $webspaceKey = null, $locale = null, $domain = null, $scheme = null, $withoutDomain = true)
    {
        // if the request analyzer null or a route is passed which is relative or inclusive a domain nothing should be
        // done (this is important for external-links in navigations)
        if (!$this->requestAnalyzer || 0 !== \strpos($route, '/')) {
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

    public function getContentRootPath($full = false)
    {
        return $this->getContentPath('/');
    }
}
