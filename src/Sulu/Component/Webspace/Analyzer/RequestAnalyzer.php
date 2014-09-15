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
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class RequestAnalyzer implements RequestAnalyzerInterface
{

    /**
     * Describes the match
     * @var int
     */
    private $matchType;

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
     * The current webspace valid for the current request
     * @var Webspace
     */
    private $webspace;

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

    /**
     * @var WebspaceContext
     */
    private $webspaceContext;

    public function __construct(WebspaceManagerInterface $webspaceManager, $environment, WebspaceContext $webspaceContext = null)
    {
        $this->webspaceManager = $webspaceManager;
        $this->environment = $environment;
        $this->webspaceContext = $webspaceContext ? : new WebspaceContext();
    }

    /**
     * Analyzes the current request, and saves the values for portal, localization and segment for further usage
     * @param Request $request The request to analyze
     * @throws Exception\UrlMatchNotFoundException
     */
    public function analyze(Request $request)
    {
        $webspaceContext = $this->webspaceContext;

        $url = $request->getHost() . $request->getPathInfo();
        $portalInformation = $this->webspaceManager->findPortalInformationByUrl(
            $url,
            $this->environment
        );

        $this->getParameter = $request->query->all();
        $this->postParameter = $request->request->all();

        if ($portalInformation != null) {
            $this->webspaceContext->setMatchType($portalInformation->getType());
            $this->webspaceContext->setRedirect($portalInformation->getRedirect());

            if ($portalInformation->getType() == RequestAnalyzerInterface::MATCH_TYPE_REDIRECT) {

                $this->webspaceContext->setPortalUrl($portalInformation->getUrl());
                $this->webspaceContext->setWebspace($portalInformation->getWebspace());

            } else {

                $this->webspaceContext->setPortalUrl($portalInformation->getUrl());
                $this->webspaceContext->setLocalization($portalInformation->getLocalization());
                $this->webspaceContext->setPortal($portalInformation->getPortal());
                $this->webspaceContext->setWebspace($portalInformation->getWebspace());

                $this->webspaceContext->setSegment($portalInformation->getSegment());
                $request->setLocale($portalInformation->getLocalization()->getLocalization());

                list($resourceLocator, $format) = $this->getResourceLocatorFromRequest(
                    $portalInformation,
                    $request
                );

                // get the path and set it on the request
                $this->webspaceContext->setResourceLocator($resourceLocator);

                if ($format) {
                    $request->setRequestFormat($format);
                }

                // get the resource locator prefix and set it
                $this->webspaceContext->setResourceLocatorPrefix(
                    substr(
                        $portalInformation->getUrl(),
                        strlen($request->getHost())
                    )
                );
            }
        } else {
            throw new UrlMatchNotFoundException($request->getUri());
        }
    }

    public function getCurrentMatchType()
    {
        return $this->webspaceContext->getMatchType();
    }

    /**
     * Returns the current webspace for this request
     * @return Webspace
     */
    public function getCurrentWebspace()
    {
        return $this->webspaceContext->getWebspace();
    }

    /**
     * Returns the current portal for this request
     * @return Portal
     */
    public function getCurrentPortal()
    {
        return $this->webspaceContext->getPortal();
    }

    /**
     * Returns the current segment for this request
     * @return Segment
     */
    public function getCurrentSegment()
    {
        return $this->webspaceContext->getSegment();
    }

    /**
     * Returns the current localization for this Request
     * @return Localization
     */
    public function getCurrentLocalization()
    {
        return $this->webspaceContext->getLocalization();
    }

    /**
     * Returns the url of the current Portal
     * @return string
     */
    public function getCurrentPortalUrl()
    {
        return $this->webspaceContext->getPortalUrl();
    }

    /**
     * Returns the redirect, null if there is no redirect
     * @return string
     */
    public function getCurrentRedirect()
    {
        return $this->webspaceContext->getRedirect();
    }

    /**
     * Returns the path of the current request, which is the url without host, language and so on
     * @return string
     */
    public function getCurrentResourceLocator()
    {
        return $this->webspaceContext->getResourceLocator();
    }

    /**
     * Returns the prefix required before the resource locator
     * @return string
     */
    public function getCurrentResourceLocatorPrefix()
    {
        return $this->webspaceContext->getResourceLocatorPrefix();
    }

    /**
     * Returns the post parameters
     * @return array
     */
    public function getCurrentPostParameter()
    {
        return $this->webspaceContext->getRequest()->request->all();
    }

    /**
     * Returns the get parameters
     * @return array
     */
    public function getCurrentGetParameter()
    {
        return $this->webspaceContext->getRequest()->query->all();
    }

    /**
     * Retrurns resourcelocator and format of current request
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
}
