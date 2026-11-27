<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
    public const CHANGE_FREQUENCY_ALWAYS = 'always';

    public const CHANGE_FREQUENCY_HOURLY = 'hourly';

    public const CHANGE_FREQUENCY_DAILY = 'daily';

    public const CHANGE_FREQUENCY_WEEKLY = 'weekly';

    public const CHANGE_FREQUENCY_MONTHLY = 'monthly';

    public const CHANGE_FREQUENCY_YEARLY = 'yearly';

    public const CHANGE_FREQUENCY_NEVER = 'never';

    /**
     * @var SitemapAlternateLink[]
     */
    private $alternateLinks;

    /**
     * @param string $loc relative URL
     * @param string $locale
     * @param string|null $defaultLocale
     * @param SitemapUrl::CHANGE_FREQUENCY_* $changefreq frequency of change
     * @param float $priority priority of page in relation to other domains
     * @param array $attributes
     * @param string|null $title human readable title of the url, used to render a human readable sitemap
     */
    public function __construct(
        private $loc,
        private $locale,
        private $defaultLocale,
        private ?\DateTimeInterface $lastmod = null,
        private $changefreq = null,
        private $priority = null,
        private $attributes = [],
        private ?string $title = null
    ) {
        $this->addAlternateLink(new SitemapAlternateLink($this->loc, $this->locale));
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
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Returns lastmod.
     *
     * @return \DateTimeInterface|null
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
     * @return $this
     */
    public function addAlternateLink(SitemapAlternateLink $alternateLink)
    {
        $this->alternateLinks[$alternateLink->getLocale()] = $alternateLink;

        return $this;
    }

    /**
     * Additional attributes to render template.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return string|null
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Human readable title of the url, is only set by providers which support it.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }
}
