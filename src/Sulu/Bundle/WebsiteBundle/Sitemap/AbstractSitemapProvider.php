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

abstract class AbstractSitemapProvider implements SitemapProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMaxPage($scheme, $host)
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function createSitemap($scheme, $host)
    {
        return new Sitemap($this->getAlias(), $this->getMaxPage($scheme, $host));
    }
}
