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
     * @var array
     */
    private $previewDefaults;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        RequestStack $requestStack,
        $environment,
        $previewDefaults = []
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->requestStack = $requestStack;
        $this->environment = $environment;

        $this->previewDefaults = array_merge(['analyticsKey' => ''], $previewDefaults);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        // determine default locale (if one exists)
        $defaultLocalization = $requestAnalyzer->getPortal()->getDefaultLocalization();
        $defaultLocale = $defaultLocalization ? $defaultLocalization->getLocalization() : null;

        return [
            'request' => [
                'webspaceKey' => $requestAnalyzer->getWebspace()->getKey(),
                'defaultLocale' => $defaultLocale,
                'locale' => $requestAnalyzer->getCurrentLocalization()->getLocalization(),
                'portalUrl' => $requestAnalyzer->getPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getResourceLocator(),
                'get' => $requestAnalyzer->getGetParameters(),
                'post' => $requestAnalyzer->getPostParameters(),
                'analyticsKey' => $requestAnalyzer->getAnalyticsKey(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveForPreview($webspaceKey, $locale)
    {
        // take first portal url
        $portalInformation = $this->webspaceManager->getPortalInformations($this->environment);
        $portalUrl = array_keys($portalInformation)[0];

        return [
            'request' => [
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                'defaultLocale' => $locale,
                'portalUrl' => $portalUrl,
                'resourceLocatorPrefix' => '',
                'resourceLocator' => '',
                'get' => $this->requestStack->getCurrentRequest()->query->all(),
                'post' => $this->requestStack->getCurrentRequest()->request->all(),
                'analyticsKey' => $this->previewDefaults['analyticsKey'],
            ],
        ];
    }
}
