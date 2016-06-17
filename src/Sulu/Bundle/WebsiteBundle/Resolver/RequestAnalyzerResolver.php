<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the request_analyzer to an array.
 */
class RequestAnalyzerResolver implements RequestAnalyzerResolverInterface
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
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        $environment
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        // determine default locale (if one exists)
        $defaultLocalization = $requestAnalyzer->getPortal()->getDefaultLocalization();
        $defaultLocale = $defaultLocalization ? $defaultLocalization->getLocalization() : null;

        $currentLocale = null;
        $currentLocalization = $requestAnalyzer->getCurrentLocalization();

        if ($currentLocalization) {
            $currentLocale = $currentLocalization->getLocale();
        }

        return [
            'request' => [
                'webspaceKey' => $requestAnalyzer->getWebspace()->getKey(),
                'portalKey' => $requestAnalyzer->getPortal()->getKey(),
                'defaultLocale' => $defaultLocale,
                'locale' => $currentLocale,
                'portalUrl' => $requestAnalyzer->getPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getResourceLocator(),
                'get' => $requestAnalyzer->getGetParameters(),
                'post' => $requestAnalyzer->getPostParameters(),
                'analyticsKey' => $requestAnalyzer->getAnalyticsKey(),
            ],
        ];
    }
}
