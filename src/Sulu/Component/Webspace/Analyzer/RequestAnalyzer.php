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

use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Segment;
use Symfony\Component\HttpFoundation\Request;

class RequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * The WebspaceManager, responsible for loading the required webspaces
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * The environment valid to analyze the request
     * @var string
     */
    private $environment;

    /**
     * The current portal valid for the current request
     * @var Portal
     */
    private $portal;

    /**
     * The current segment valid for the current request
     * @var Segment
     */
    private $segment;

    /**
     * The current localization valid for the current request
     * @var Localization
     */
    private $localization;

    /**
     * The redirect, null if not existent
     * @var string
     */
    private $redirect;

    /**
     * The url of the current portal
     * @var string
     */
    private $portalUrl;

    /**
     * The path of the current request
     * @var string
     */
    private $resourceLocator;

    /**
     * The prefix required before the resource locator
     * @var string
     */
    private $resourceLocatorPrefix;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
    }

    /**
     * Analyzes the current request, and saves the values for portal, localization and segment for further usage
     * @param Request $request The request to analyze
     * @throws Exception\UrlMatchNotFoundException
     */
    public function analyze(Request $request)
    {
        $url = $request->getHost() . $request->getRequestUri();
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl(
            $url,
            $this->environment
        );

        if ($portalInformation != null) {
            if (array_key_exists('redirect', $portalInformation)) {
                $this->setCurrentPortalUrl($portalInformation['url']);
                $this->setCurrentRedirect($portalInformation['redirect']);
            } else {
                $this->setCurrentPortalUrl($portalInformation['url']);
                $this->setCurrentLocalization($portalInformation['localization']);
                $this->setCurrentPortal($portalInformation['portal']);

                if (array_key_exists('segment', $portalInformation)) {
                    $this->setCurrentSegment($portalInformation['segment']);
                }

                // get the path and set it on the request
                $this->setCurrentResourceLocator(
                    substr(
                        $request->getHost() . $request->getRequestUri(),
                        strlen($portalInformation['url'])
                    )
                );

                // get the resource locator prefix and set it
                $this->setCurrentResourceLocatorPrefix(
                    substr(
                        $portalInformation['url'],
                        strlen($request->getHost())
                    )
                );
            }
        } else {
            throw new UrlMatchNotFoundException($request->getUri());
        }
    }

    /**
     * Returns the current portal for this request
     * @return Portal
     */
    public function getCurrentPortal()
    {
        return $this->portal;
    }

    /**
     * Returns the current segment for this request
     * @return Segment
     */
    public function getCurrentSegment()
    {
        return $this->segment;
    }

    /**
     * Returns the current localization for this Request
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->localization;
    }

    /**
     * Returns the url of the current Portal
     * @return string
     */
    public function getCurrentPortalUrl()
    {
        return $this->portalUrl;
    }

    /**
     * Returns the redirect, null if there is no redirect
     * @return string
     */
    public function getCurrentRedirect()
    {
        return $this->redirect;
    }

    /**
     * Returns the path of the current request, which is the url without host, language and so on
     * @return string
     */
    public function getCurrentResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the prefix required before the resource locator
     * @return string
     */
    public function getCurrentResourceLocatorPrefix()
    {
        return $this->resourceLocatorPrefix;
    }

    /**
     * Sets the current localization
     * @param \Sulu\Component\Webspace\Localization $localization
     */
    protected function setCurrentLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * Sets the current portal
     * @param \Sulu\Component\Webspace\Portal $portal
     */
    protected function setCurrentPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * Sets the current segment
     * @param \Sulu\Component\Webspace\Segment $segment
     */
    protected function setCurrentSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Sets the redirect
     * @param string $redirect
     */
    public function setCurrentRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Sets the url of the current portal
     * @param string $portalUrl
     */
    public function setCurrentPortalUrl($portalUrl)
    {
        $this->portalUrl = $portalUrl;
    }

    /**
     * Sets the path of the current request
     * @param $path
     */
    public function setCurrentResourceLocator($path)
    {
        $this->resourceLocator = $path;
    }

    /**
     * Sets the prefix require before the resource locator
     * @param string $resourceLocatorPrefix
     */
    public function setCurrentResourceLocatorPrefix($resourceLocatorPrefix)
    {
        $this->resourceLocatorPrefix = $resourceLocatorPrefix;
    }
}
