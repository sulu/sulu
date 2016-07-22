<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Sitemap;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SitemapXMLGeneratorTest extends SuluTestCase
{
    protected function setUp()
    {
        parent::initPhpcr();
    }

    public function testSitemapSingleLocale()
    {
        $client = $this->createClient(
            [
                'environment' => 'prod',
                'sulu_context' => 'website',
            ],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
        $client->request('GET', 'http://sulu.lo/sitemap.xml');

        $content = $client->getResponse()->getContent();

        $date = '2016-03-01';
        $this->assertSitemapXml(<<<'EOT'
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <url>
    <loc>http://sulu.lo</loc>
    <lastmod>:date</lastmod>
  </url>
</urlset>
EOT
            ,
            $content
        );
    }

    public function testSitemap()
    {
        $client = $this->createClient(
            [
                'environment' => 'prod',
                'sulu_context' => 'website',
            ],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
        $client->request('GET', 'http://test.lo/sitemap.xml');
        $content = $client->getResponse()->getContent();

        $this->assertSitemapXml(<<<'EOT'
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
  <url>
    <loc>http://test.lo/en</loc>
    <lastmod>:date</lastmod>
    <xhtml:link rel="alternate" hreflang="en" href="http://test.lo/en"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="http://test.lo/en"/>
    <xhtml:link rel="alternate" hreflang="en-us" href="http://test.lo/en-us"/>
  </url>
  <url>
    <loc>http://test.lo/en-us</loc>
    <lastmod>:date</lastmod>
    <xhtml:link rel="alternate" hreflang="en" href="http://test.lo/en"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="http://test.lo/en"/>
    <xhtml:link rel="alternate" hreflang="en-us" href="http://test.lo/en-us"/>
  </url>
</urlset>
EOT
            ,
            $content
        );
    }

    private function assertSitemapXml($expected, $actual)
    {
        $dom = new \DOMDocument();
        $dom->loadXml($actual);
        $dom->formatOutput = true;

        $this->assertContains(
            str_replace(':date', date('Y-m-d'), $expected),
            $dom->saveXml()
        );
    }
}
