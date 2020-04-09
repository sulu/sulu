<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Application;

use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;

class TestSitemapProvider implements SitemapProviderInterface
{
    public function build($page, $scheme, $host)
    {
        if ('sulu.index' !== $host) {
            return [];
        }

        return [
            new SitemapUrl('http://sulu.io/world', 'en', 'en'),
        ];
    }

    public function createSitemap($scheme, $host)
    {
        return new Sitemap('test', $this->getMaxPage($scheme, $host));
    }

    public function getAlias()
    {
        return 'test';
    }

    public function getMaxPage($scheme, $host)
    {
        if ('sulu.index' !== $host) {
            return 0;
        }

        return 1;
    }
}
