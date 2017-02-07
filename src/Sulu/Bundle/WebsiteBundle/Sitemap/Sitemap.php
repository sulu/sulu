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
 * Contains information for a complete sitemap.
 */
class Sitemap
{
    /**
     * Unique identification.
     *
     * @var string
     */
    private $alias;

    /**
     * Maximum pages of sitemap.
     *
     * @var int
     */
    private $maxPage;

    /**
     * Datetime of last modification.
     *
     * @var \DateTime
     */
    private $lastmod;

    /**
     * @param string $alias
     * @param int $maxPage
     * @param \DateTime $lastmod
     */
    public function __construct($alias, $maxPage, \DateTime $lastmod = null)
    {
        $this->alias = $alias;
        $this->maxPage = $maxPage;
        $this->lastmod = $lastmod;
    }

    /**
     * Returns alias.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Returns max-page.
     *
     * @return int
     */
    public function getMaxPage()
    {
        return $this->maxPage;
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
}
