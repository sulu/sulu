<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

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

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        $portal = $requestAnalyzer->getPortal();
        $webspace = $requestAnalyzer->getWebspace();

        // determine default locale (if one exists)
        $defaultLocalization = $portal?->getDefaultLocalization();
        $defaultLocale = $defaultLocalization ? $defaultLocalization->getLocale() : null;

        $segment = $requestAnalyzer->getSegment();
        $segmentKey = $segment ? $segment->getKey() : null;

        return [
            'request' => [
                'webspaceKey' => $webspace?->getKey(),
                'webspaceName' => $webspace?->getName(),
                'segmentKey' => $segmentKey,
                'portalKey' => $portal?->getKey(),
                'portalName' => $portal?->getName(),
                'defaultLocale' => $defaultLocale,
                'portalUrl' => $requestAnalyzer->getPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getResourceLocator(),
            ],
        ];
    }
}
