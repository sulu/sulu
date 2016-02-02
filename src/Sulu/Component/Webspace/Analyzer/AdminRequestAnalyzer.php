<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

/**
 * Request analyzer for the admin context.
 */
class AdminRequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * The WebspaceManager, responsible for loading the required webspaces.
     *
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * The environment valid to analyze the request.
     *
     * @var string
     */
    private $environment;

    /**
     * The current webspace valid for the request.
     *
     * @var Webspace
     */
    private $webspace;

    /**
     * The current localization for the request.
     *
     * @var Localization
     */
    private $localization;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * Analyzes the current request, and saves the values for portal, localization and segment for further usage.
     *
     * @param Request $request The request to analyze
     *
     * @throws Exception\UrlMatchNotFoundException
     */
    public function analyze(Request $request)
    {
        $webspaceKey = $request->get('webspace');
        // TODO rename to locale
        $locale = $request->get('language');

        if ($webspaceKey !== null) {
            $this->webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
        }
        if ($this->webspace !== null && $locale !== null) {
            $this->localization = $this->webspace->getLocalization($locale);
        }
    }

    /**
     * set webspace for current request.
     *
     * @param string $webspaceKey
     */
    public function setWebspaceKey($webspaceKey)
    {
        $this->webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);
    }

    /**
     * set localization for current request.
     *
     * @param string $locale
     */
    public function setLocalizationCode($locale)
    {
        $this->localization = $this->webspace->getLocalization($locale);
    }

    public function getMatchType()
    {
        return;
    }

    /**
     * Returns the current webspace for this request.
     *
     * @return Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }

    /**
     * Returns the current portal for this request.
     *
     * @return Portal
     */
    public function getPortal()
    {
        return;
    }

    /**
     * Returns the current segment for this request.
     *
     * @return Segment
     */
    public function getSegment()
    {
        return;
    }

    /**
     * Returns the current localization for this Request.
     *
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->localization;
    }

    /**
     * Returns the url of the current Portal.
     *
     * @return string
     */
    public function getPortalUrl()
    {
        return;
    }

    /**
     * Returns the redirect, null if there is no redirect.
     *
     * @return string
     */
    public function getRedirect()
    {
        return;
    }

    /**
     * Returns the path of the current request, which is the url without host, language and so on.
     *
     * @return string
     */
    public function getResourceLocator()
    {
        return;
    }

    /**
     * Returns the prefix required before the resource locator.
     *
     * @return string
     */
    public function getResourceLocatorPrefix()
    {
        return;
    }

    /**
     * Returns the post parameters.
     *
     * @return array
     */
    public function getPostParameters()
    {
        return;
    }

    /**
     * Returns the get parameters.
     *
     * @return array
     */
    public function getGetParameters()
    {
        return;
    }

    /**
     * Returns the analytics key.
     *
     * @return string
     */
    public function getAnalyticsKey()
    {
        return '';
    }

    /**
     * Returns portal-information of request.
     *
     * @return PortalInformation
     */
    public function getPortalInformation()
    {
        return;
    }
}
