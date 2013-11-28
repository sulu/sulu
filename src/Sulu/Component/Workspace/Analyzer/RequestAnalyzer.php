<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Workspace\Analyzer;

use Sulu\Component\Workspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Workspace\Localization;
use Sulu\Component\Workspace\Manager\WorkspaceManagerInterface;
use Sulu\Component\Workspace\Portal;
use Sulu\Component\Workspace\Segment;
use Symfony\Component\HttpFoundation\Request;

class RequestAnalyzer implements RequestAnalyzerInterface
{
    /**
     * The WorkspaceManager, responsible for loading the required workspaces
     * @var WorkspaceManagerInterface
     */
    private $workspaceManager;

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

    public function __construct(WorkspaceManagerInterface $workspaceManager, $environment)
    {
        $this->workspaceManager = $workspaceManager;
        $this->environment = $environment;
    }

    /**
     * Analyzes the current request, and saves the values for portal, localization and segment for further usage
     * @param Request $request The request to analyze
     */
    public function analyze(Request $request)
    {
        $url = $request->getHost() . $request->getRequestUri();
        $portalInformation = $this->workspaceManager->findPortalInformationByUrl(
            $url,
            $this->environment
        );

        if ($portalInformation != null) {
            if (array_key_exists('redirect', $portalInformation)) {
                $this->setPortalUrl($portalInformation['url']);
                $this->setRedirect($portalInformation['redirect']);
            } else {
                $this->setPortalUrl($portalInformation['url']);
                $this->setLocalization($portalInformation['localization']);
                $this->setPortal($portalInformation['portal']);

                if (array_key_exists('segment', $portalInformation)) {
                    $this->setSegment($portalInformation['segment']);
                }
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
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * Sets the current localization
     * @param \Sulu\Component\Workspace\Localization $localization
     */
    protected function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * Sets the current portal
     * @param \Sulu\Component\Workspace\Portal $portal
     */
    protected function setPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * Sets the current segment
     * @param \Sulu\Component\Workspace\Segment $segment
     */
    protected function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Sets the redirect
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Sets the url of the current portal
     * @param string $portalUrl
     */
    public function setPortalUrl($portalUrl)
    {
        $this->portalUrl = $portalUrl;
    }
}
