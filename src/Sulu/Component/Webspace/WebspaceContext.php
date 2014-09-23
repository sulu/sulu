<?php

namespace Sulu\Component\Webspace;

use Symfony\Component\HttpFoundation\Request;

/**
 * The webspace context holds information about the current
 * webspace request.
 */
class WebspaceContext
{
    protected $masterRequest;

    /**
     * {@inheritDoc}
     */
    public function getMatchType()
    {
        return $this->matchType;
    }

    /**
     * {@inheritDoc}
     */
    public function setMatchType($matchType)
    {
        $this->matchType = $matchType;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebspace()
    {
        return $this->webspace;
    }

    /**
     * {@inheritDoc}
     */
    public function setWebspace($webspace)
    {
        $this->webspace = $webspace;
    }

    /**
     * {@inheritDoc}
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * {@inheritDoc}
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * {@inheritDoc}
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * {@inheritDoc}
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * {@inheritDoc}
     */
    public function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * {@inheritDoc}
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * {@inheritDoc}
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * {@inheritDoc}
     */
    public function getPortalUrl()
    {
        return $this->portalUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function setPortalUrl($portalUrl)
    {
        $this->portalUrl = $portalUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function setResourceLocator($resourceLocator)
    {
        $this->resourceLocator = $resourceLocator;
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceLocatorPrefix()
    {
        return $this->resourceLocatorPrefix;
    }

    /**
     * {@inheritDoc}
     */
    public function setResourceLocatorPrefix($resourceLocatorPrefix)
    {
        $this->resourceLocatorPrefix = $resourceLocatorPrefix;
    }

    /**
     * Request
     *
     * @param Request
     */
    public function setMasterRequest(Request $masterRequest)
    {
        $this->masterRequest = $masterRequest;
    }

    public function getMasterRequest()
    {
        return $this->masterRequest;
    }
}
