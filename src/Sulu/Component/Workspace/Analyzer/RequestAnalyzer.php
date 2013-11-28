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
        $portalInformation = $this->workspaceManager->findPortalInformationByUrl(
            $request->getUri(),
            $this->environment
        );

        $this->setLocalization($portalInformation['localization']);
        $this->setPortal($portalInformation['portal']);

        if (array_key_exists('segment', $portalInformation)) {
            $this->setSegment($portalInformation['segment']);
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
}
