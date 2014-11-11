<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the request_analyzer to an array
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
        $this->environment = $environment;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        // determine default locale (if one exists)
        $defaultLocale = $requestAnalyzer->getCurrentPortal()->getDefaultLocalization();
        if ($defaultLocale !== null) {
            $defaultLocale = $defaultLocale->getLocalization();
        }

        return array(
            'request' => array(
                'webspaceKey' => $requestAnalyzer->getCurrentWebspace()->getKey(),
                'defaultLocale' => $defaultLocale,
                'locale' => $requestAnalyzer->getCurrentLocalization()->getLocalization(),
                'portalUrl' => $requestAnalyzer->getCurrentPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getCurrentResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getCurrentResourceLocator(),
                'get' => $requestAnalyzer->getCurrentGetParameter(),
                'post' => $requestAnalyzer->getCurrentPostParameter()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function resolveForPreview($webspaceKey, $locale)
    {
        // take first portal url
        $portalInformation = $this->webspaceManager->getPortalInformations($this->environment);
        $portalUrl = array_keys($portalInformation)[0];

        return array(
            'request' => array(
                'webspaceKey' => $webspaceKey,
                'locale' => $locale,
                'defaultLocale' => $locale,
                'portalUrl' => $portalUrl,
                'resourceLocatorPrefix' => '',
                'resourceLocator' => '',
                'get' => array(),
                'post' => array()
            )
        );
    }
}
