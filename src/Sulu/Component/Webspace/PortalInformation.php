<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace;

/**
 * This class represents the information for a given URL
 * @package Sulu\Component\Webspace
 */
class PortalInformation
{
    /**
     * The type of the match
     * @var int
     */
    private $type;

    /**
     * The webspace for this portal information
     * @var Webspace
     */
    private $webspace;

    /**
     * The portal for this portal information
     * @var Portal
     */
    private $portal;

    /**
     * The localization for this portal information
     * @var Localization
     */
    private $localization;

    /**
     * The segment for this portal information
     * @var Segment
     */
    private $segment;

    /**
     * The url for this portal information
     * @var string
     */
    private $url;

    /**
     * @var string The url to redirect to
     */
    private $redirect;

    public function __construct(
        $type,
        Webspace $webspace = null,
        Portal $portal = null,
        Localization $localization = null,
        $url,
        Segment $segment = null,
        $redirect = null
    )
    {
        $this->setType($type);
        $this->setWebspace($webspace);
        $this->setPortal($portal);
        $this->setLocalization($localization);
        $this->setSegment($segment);
        $this->setUrl($url);
        $this->setRedirect($redirect);
    }

    /**
     * @param \Sulu\Component\Webspace\Localization $localization
     */
    public function setLocalization($localization)
    {
        $this->localization = $localization;
    }

    /**
     * @return \Sulu\Component\Webspace\Localization
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * @param \Sulu\Component\Webspace\Portal $portal
     */
    public function setPortal($portal)
    {
        $this->portal = $portal;
    }

    /**
     * @return \Sulu\Component\Webspace\Portal
     */
    public function getPortal()
    {
        return $this->portal;
    }

    /**
     * @param string $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param \Sulu\Component\Webspace\Segment $segment
     */
    public function setSegment($segment)
    {
        $this->segment = $segment;
    }

    /**
     * @return \Sulu\Component\Webspace\Segment
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        $hostLength = strpos($this->url, '/');
        $hostLength = ($hostLength === false) ? strlen($this->url) : $hostLength;

        return substr($this->url, 0, $hostLength);
    }

    /**
     * @param \Sulu\Component\Webspace\Webspace $webspace
     */
    public function setWebspace($webspace)
    {
        $this->webspace = $webspace;
    }

    /**
     * @return \Sulu\Component\Webspace\Webspace
     */
    public function getWebspace()
    {
        return $this->webspace;
    }
}
