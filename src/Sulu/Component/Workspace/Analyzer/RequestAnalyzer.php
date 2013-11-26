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


use Sulu\Component\Workspace\Portal;
use Sulu\Component\Workspace\Segment;

class RequestAnalyzer implements RequestAnalyzerInterface
{
    private $currentPortal;

    private $currentSegment;

    private $currentLanguage;

    private $currentCountry;

    /**
     * Returns the current portal for this request
     * @return \Sulu\Component\Workspace\Portal
     */
    public function getCurrentPortal()
    {
        return $this->currentPortal;
    }

    /**
     * Sets the current portal for this request
     * @param Portal $portal
     */
    public function setCurrentPortal(Portal $portal)
    {
        $this->currentPortal = $portal;
    }

    /**
     * Returns the current segment for this request
     * @return \Sulu\Component\Workspace\Segment
     */
    public function getCurrentSegment()
    {
        return $this->currentSegment;
    }

    /**
     * Sets the current segment for this request
     * @param Segment $segment
     */
    public function setCurrentSegment(Segment $segment)
    {
        $this->currentSegment = $segment;
    }

    /**
     * Returns the current country for this Request
     * @return string
     */
    public function getCurrentCountry()
    {
        return $this->currentCountry;
    }

    /**
     * Sets the current country for this request
     * @param string $country
     */
    public function setCurrentCountry($country)
    {
        $this->currentCountry = $country;
    }

    /**
     * Returns the current language for this request
     * @return string
     */
    public function getCurrentLanguage()
    {
        return $this->currentLanguage;
    }

    /**
     * Sets the current language for this request
     * @param string $language
     */
    public function setCurrentLanguage($language)
    {
        $this->currentLanguage = $language;
    }
}
