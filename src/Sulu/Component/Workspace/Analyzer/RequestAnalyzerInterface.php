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

/**
 * Defines the interface for the request analyzer, who is responsible for return the required information for the
 * current request
 * @package Sulu\Component\Workspace\Analyzer
 */
interface RequestAnalyzerInterface
{
    /**
     * Returns the current portal for this request
     * @return \Sulu\Component\Workspace\Portal
     */
    public function getCurrentPortal();

    /**
     * Sets the current portal for this request
     * @param Portal $portal
     */
    public function setCurrentPortal(Portal $portal);

    /**
     * Returns the current segment for this request
     * @return \Sulu\Component\Workspace\Segment
     */
    public function getCurrentSegment();

    /**
     * Sets the current segment for this request
     * @param Segment $segment
     */
    public function setCurrentSegment(Segment $segment);

    /**
     * Returns the current country for this Request
     * @return string
     */
    public function getCurrentCountry();

    /**
     * Sets the current country for this request
     * @param string $country
     */
    public function setCurrentCountry($country);

    /**
     * Returns the current language for this request
     * @return string
     */
    public function getCurrentLanguage();

    /**
     * Sets the current language for this request
     * @param string $language
     */
    public function setCurrentLanguage($language);
}
