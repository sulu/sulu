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

    function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->environment = $environment;
        $this->webspaceManager = $webspaceManager;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(RequestAnalyzerInterface $requestAnalyzer)
    {
        return array(
            'request' => array(
                'portalUrl' => $requestAnalyzer->getCurrentPortalUrl(),
                'resourceLocatorPrefix' => $requestAnalyzer->getCurrentResourceLocatorPrefix(),
                'resourceLocator' => $requestAnalyzer->getCurrentResourceLocator()
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
                'portalUrl' => $portalUrl,
                'resourceLocatorPrefix' => '',
                'resourceLocator' => ''
            )
        );
    }
} 
