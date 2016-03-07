<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Sitemap;

use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\WorkflowStage;

class SitemapXMLGeneratorTest extends SuluTestCase
{
    protected function setUp()
    {
        parent::initPhpcr();

        /** @var SessionInterface $session */
        $session = $this->db('PHPCR')->getOm()->getPhpcrSession();
        $cmf = $session->getRootNode()->getNode('cmf');

        // we should use the doctrinephpcrbundle repository initializer to do this.
        $webspace = $cmf->addNode('test_io');
        $webspace->addMixin('mix:referenceable');

        $content = $webspace->addNode('contents');
        $content->setProperty('i18n:en-template', 'default');
        $content->setProperty('i18n:en-creator', 1);
        $content->setProperty('i18n:en-created', new \DateTime());
        $content->setProperty('i18n:en-changer', 1);
        $content->setProperty('i18n:en-changed', new \DateTime());
        $content->setProperty('i18n:en-title', 'Homepage');
        $content->setProperty('i18n:en-state', WorkflowStage::PUBLISHED);
        $content->setProperty('i18n:en-published', new \DateTime());
        $content->setProperty('i18n:en-url', '/');
        $content->setProperty('i18n:en_us-template', 'default');
        $content->setProperty('i18n:en_us-creator', 1);
        $content->setProperty('i18n:en_us-created', new \DateTime());
        $content->setProperty('i18n:en_us-changer', 1);
        $content->setProperty('i18n:en_us-changed', new \DateTime());
        $content->setProperty('i18n:en_us-title', 'Homepage');
        $content->setProperty('i18n:en_us-state', WorkflowStage::PUBLISHED);
        $content->setProperty('i18n:en_us-published', new \DateTime());
        $content->setProperty('i18n:en_us-url', '/');
        $content->addMixin('sulu:home');

        $session->save();
        $nodes = $webspace->addNode('routes');
        foreach (['de', 'de_at', 'en', 'en_us', 'fr'] as $locale) {
            $localeNode = $nodes->addNode($locale);
            $localeNode->setProperty('sulu:content', $content);
            $localeNode->setProperty('sulu:history', false);
            $localeNode->addMixin('sulu:path');
        }

        $session->save();
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
        $client->request('GET', 'http://sulu.lo/sulu_website/sitemap.xml');

        $content = $client->getResponse()->getContent();

        $this->assertContains('<url><loc>http://sulu.lo</loc><lastmod>' . date('Y-m-d') . '</lastmod></url>', $content);
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
        $client->request('GET', 'http://test.lo/sulu_website/sitemap.xml');

        $date = date('Y-m-d');

        $content = $client->getResponse()->getContent();

        $this->assertContains(
            '<url><loc>http://test.lo</loc><lastmod>' . $date . '</lastmod><xhtml:link rel="alternate" hreflang="en" href="http://test.lo"/><xhtml:link rel="alternate" hreflang="x-default" href="http://test.lo"/><xhtml:link rel="alternate" hreflang="en-us" href="http://test.lo/en-us"/></url><url><loc>http://test.lo/en-us</loc><lastmod>' . $date . '</lastmod><xhtml:link rel="alternate" hreflang="en" href="http://test.lo"/><xhtml:link rel="alternate" hreflang="x-default" href="http://test.lo"/><xhtml:link rel="alternate" hreflang="en-us" href="http://test.lo/en-us"/></url>',
            $content
        );
    }
}
