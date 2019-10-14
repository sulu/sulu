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
 * Interface for sitemap-dumper.
 */
interface XmlSitemapDumperInterface
{
    /**
     * Returns path of sitemap-index.
     *
     * @param string $scheme
     * @param string $host
     *
     * @return string
     */
    public function getIndexDumpPath($scheme, $host);

    /**
     * Returns path of sitemap.
     *
     * @param string $scheme
     * @param string $host
     * @param string $alias
     * @param int $page
     *
     * @return string
     */
    public function getDumpPath($scheme, $host, $alias, $page);

    /**
     * Dump sitemaps for specific host.
     *
     * @param string $host
     * @param string $scheme
     *
     * @throws \InvalidArgumentException
     */
    public function dumpHost($scheme, $host);
}
