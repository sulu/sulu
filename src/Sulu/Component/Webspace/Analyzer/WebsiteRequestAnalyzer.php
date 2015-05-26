<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer;

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * Describes the match.
     *
     * @var int
     */
    private $matchType;

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
     * The current webspace valid for the current request.
     *
     * @var Webspace
     */
    private $webspace;

    /**
     * The current portal valid for the current request.
     *
     * @var Portal
     */
    private $portal;

    /**
     * The current segment valid for the current request.
     *
     * @var Segment
     */
    private $segment;

    /**
     * The current localization valid for the current request.
     *
     * @var Localization
     */
    private $localization;

    /**
     * The redirect, null if not existent.
     *
     * @var string
     */
    private $redirect;

    /**
     * The url of the current portal.
     *
     * @var string
     */
    private $portalUrl;

    /**
     * The path of the current request.
     *
     * @var string
     */
    private $resourceLocator;

    /**
     * The prefix required before the resource locator.
     *
     * @var string
     */
    private $resourceLocatorPrefix;

    /**
     * Get parameter of request.
     *
     * @var array
     */
    private $getParameter;

    /**
     * Post parameter of request.
     *
     * @var array
     */
    private $postParameter;

    /**
     * Analytics key of request.
     *
     * @var string
     */
    private $analyticsKey;

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
        $url = $request->getHost() . $request->getPathInfo();
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl(
            $url,
            $this->environment
        );

        $this->getParameter = $request->query->all();
        $this->postParameter = $request->request->all();

        if ($portalInformation === null) {
            throw new UrlMatchNotFoundException($request->getUri());
        }

        $this->setCurrentMatchType($portalInformation->getType());
        $this->setCurrentRedirect($portalInformation->getRedirect());
        $this->analyticsKey = $portalInformation->getAnalyticsKey();

        if ($portalInformation->getType() == RequestAnalyzerInterface::MATCH_TYPE_REDIRECT) {
            $this->setCurrentPortalUrl($portalInformation->getUrl());
            $this->setCurrentWebspace($portalInformation->getWebspace());

            return;
        }

        $this->setCurrentPortalUrl($portalInformation->getUrl());
        $this->setCurrentLocalization($portalInformation->getLocalization());
        $this->setCurrentPortal($portalInformation->getPortal());
        $this->setCurrentWebspace($portalInformation->getWebspace());
        $this->setCurrentSegment($portalInformation->getSegment());

        $request->setLocale($portalInformation->getLocalization()->getLocalization());

        list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
            $portalInformation,
            $request
        );

        // get the path and set it on the request
        $this->setCurrentResourceLocator($resourceLocator);

        if ($format) {
            $request->setRequestFormat($format);
        }

        // get the resource locator prefix and set it
        $this->setCurrentResourceLocatorPrefix(
            substr(
                $portalInformation->getUrl(),
                strlen($request->getHost())
            )
        );
    }

    public function getMatchType()
    {
        return $this->matchType;
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
        return $this->portal;
    }

    /**
     * Returns the current segment for this request.
     *
     * @return Segment
     */
    public function getSegment()
    {
        return $this->segment;
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
        return $this->portalUrl;
    }

    /**
     * Returns the redirect, null if there is no redirect.
     *
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Returns the path of the current request, which is the url without host, language and so on.
     *
     * @return string
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the prefix required before the resource locator.
     *
     * @return string
     */
    public function getResourceLocatorPrefix()
    {
        return $this->resourceLocatorPrefix;
    }

    /**
     * Returns the post parameters.
     *
     * @return array
     */
    public function getPostParameters()
    {
        return $this->postParameter;
    }

    /**
     * Returns the get parameters.
     *
     * @return array
     */
    public function getGetParameters()
    {
        return $this->getParameter;
    }

    /**
     * Sets the current match type.
     *
     * @param int $matchType
     */
    public function setCurrentMatchType($matchType)
    {
        $this->matchType = $matchType;
    }

    /**
     * Sets the current localization.
     *
     * @param Localization $localization
     */
    protected function setCurrentLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * Sets the current webspace.
     *
     * @param \Sulu\Component\Webspace\Webspace $webspace
     */
    protected function setCurrentWebspace($webspace)
    {
        $this->webspace = $webspace;
    }

    /**
     * Sets the current portal.
     *
     * @param \Sulu\Component\Webspace\Portal $portal
     */
    protected function setCurrentPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * Sets the current segment.
     *
     * @param \Sulu\Component\Webspace\Segment $segment
     */
    protected function setCurrentSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Sets the redirect.
     *
     * @param string $redirect
     */
    protected function setCurrentRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Sets the url of the current portal.
     *
     * @param string $portalUrl
     */
    protected function setCurrentPortalUrl($portalUrl)
    {
        $this->portalUrl = $portalUrl;
    }

    /**
     * Sets the path of the current request.
     *
     * @param $path
     */
    protected function setCurrentResourceLocator($path)
    {
        $this->resourceLocator = $path;
    }

    /**
     * Sets the prefix require before the resource locator.
     *
     * @param string $resourceLocatorPrefix
     */
    protected function setCurrentResourceLocatorPrefix($resourceLocatorPrefix)
    {
        $this->resourceLocatorPrefix = $resourceLocatorPrefix;
    }

    /**
     * Retrurns resourcelocator and format of current request.
     */
    private function getResourceLocatorFromRequest(
        PortalInformation $portalInformation,
        Request $request
    ) {
        $path = $request->getPathInfo();

        // extract file and extension info
        $pathParts = explode('/', $path);
        $fileInfo = explode('.', array_pop($pathParts));

        $path = rtrim(implode('/', $pathParts), '/') . '/' . $fileInfo[0];
        if (sizeof($fileInfo) > 1) {
            $formatResult = $fileInfo[1];
        } else {
            $formatResult = null;
        }

        $resourceLocator = substr(
            $request->getHost() . $path,
            strlen($portalInformation->getUrl())
        );

        return array($resourceLocator, $formatResult);
    }

    /**
     * Returns the analytics key.
     *
     * @return string
     */
    public function getAnalyticsKey()
    {
        return $this->analyticsKey;
    }
}
