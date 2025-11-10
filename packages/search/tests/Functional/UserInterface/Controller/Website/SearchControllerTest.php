<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Search\Tests\Functional\UserInterface\Controller\Website;

use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SearchControllerTest extends WebsiteTestCase
{
    use CreatePageTrait;

    protected static KernelBrowser $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createWebsiteClient();
        parent::setUpBeforeClass();
        self::purgeDatabase();
        $engine = self::getContainer()->get('cmsig_seal.engine.default');
        $engine->createSchema(['return_slow_promise_result' => true]);
        $documents = [
            [
                'id' => 'pages::019a261c-28e1-72fe-a746-e518f8711bfc::de',
                'resourceKey' => 'pages',
                'resourceId' => '019a261c-28e1-72fe-a746-e518f8711bfc',
                'locale' => 'de',
                'webspaces' => ['sulu-io'],
                'title' => 'Hello World',
                'url' => '/hello-world',
                'content' => [],
                'authoredAt' => '2024-11-29 15:30:00',
            ],
            [
                'id' => 'pages::019a261c-2999-72ab-b189-8715bc66b773::de',
                'resourceKey' => 'pages',
                'resourceId' => '019a261c-2999-72ab-b189-8715bc66b773',
                'locale' => 'de',
                'webspaces' => ['sulu-io'],
                'title' => 'Example Product Page',
                'url' => '/example-product-page',
                'content' => ['This is an example content for the Product page.'],
                'authoredAt' => '2024-11-29 15:30:00',
            ],
        ];

        foreach ($documents as $document) {
            $engine->saveDocument('website', $document);
        }
    }

    public function testSearchExactTerm(): void
    {
        $crawler = self::$client->request('GET', 'http://sulu.io/de/search?q=Product');
        $response = self::$client->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertCount(1, $crawler->filter('.search-result'));
        $this->assertSame('Product', $crawler->filter('mark')->html());
    }

    public function testSearchExactTermWrongLocale(): void
    {
        $crawler = self::$client->request('GET', 'http://sulu.io/en/search?q=Product');
        $response = self::$client->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertCount(0, $crawler->filter('.search-result'));
    }

    public function testSearchExactTermWrongWebspace(): void
    {
        $crawler = self::$client->request('GET', 'http://blog.io/en/search?q=Product');
        $response = self::$client->getResponse();

        static::assertHttpStatusCode(200, $response);
        $this->assertCount(0, $crawler->filter('.search-result'));
    }
}
