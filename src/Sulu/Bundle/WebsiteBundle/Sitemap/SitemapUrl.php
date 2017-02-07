<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Sitemap;

/**
 * Contains information for a single sitemap entry.
 */
class SitemapUrl
{
    /**
     * Constants which indicates the change frequency (google will use them).
     */
    const CHANGE_FREQUENCY_ALWAYS = 'always';
    const CHANGE_FREQUENCY_HOURLY = 'hourly';
    const CHANGE_FREQUENCY_DAILY = 'daily';
    const CHANGE_FREQUENCY_WEEKLY = 'weekly';
    const CHANGE_FREQUENCY_MONTHLY = 'monthly';
    const CHANGE_FREQUENCY_YEARLY = 'yearly';
    const CHANGE_FREQUENCY_NEVER = 'never';

    /**
     * Relative URL.
     *
     * @var string
     */
    private $loc;

    /**
     * Datetime of last modification.
     *
     * @var \DateTime
     */
    private $lastmod;

    /**
     * Frequency of change.
     *
     * See constants SitemapUrl::CHANGE_FREQUENCY_*.
     *
     * @var string
     */
    private $changefreq;

    /**
     * Priority of page in relation to other domains.
     *
     * @var float
     */
    private $priority;

    /**
     * @var SitemapAlternateLink[]
     */
    private $alternateLinks;

    /**
     * @param string $loc
     * @param \DateTime $lastmod
     * @param string $changefreq
     * @param float $priority
     */
    public function __construct($loc, \DateTime $lastmod = null, $changefreq = null, $priority = null)
    {
        $this->loc = $loc;
        $this->lastmod = $lastmod;
        $this->changefreq = $changefreq;
        $this->priority = $priority;
    }

    /**
     * Returns loc.
     *
     * @return string
     */
    public function getLoc()
    {
        return $this->loc;
    }

    /**
     * Returns lastmod.
     *
     * @return \DateTime
     */
    public function getLastmod()
    {
        return $this->lastmod;
    }

    /**
     * Returns changefreq.
     *
     * @return string
     */
    public function getChangefreq()
    {
        return $this->changefreq;
    }

    /**
     * Returns priority.
     *
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Returns alternate-links.
     *
     * @return SitemapAlternateLink[]
     */
    public function getAlternateLinks()
    {
        return $this->alternateLinks;
    }

    /**
     * Add new alternate-link.
     *
     * @param SitemapAlternateLink $alternateLink
     *
     * @return $this
     */
    public function addAlternateLink(SitemapAlternateLink $alternateLink)
    {
        $this->alternateLinks[] = $alternateLink;

        return $this;
    }
}
