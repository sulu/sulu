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
 * Contains information for a complete sitemap.
 */
class Sitemap
{
    /**
     * @param string $alias unique identification
     * @param int $maxPage maximum pages of sitemap
     * @param ?\DateTime $lastmod datetime of last modification
     */
    public function __construct(
        private $alias,
        private $maxPage,
        private ?\DateTime $lastmod = null
    ) {
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
     * @return \DateTime|null
     */
    public function getLastmod()
    {
        return $this->lastmod;
    }
}
