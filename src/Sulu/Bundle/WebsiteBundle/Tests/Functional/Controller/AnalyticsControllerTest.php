<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManagerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AnalyticsControllerTest extends SuluTestCase
{
    /**
     * @var AnalyticsManagerInterface
     */
    private $analyticsManager;

    /**
     * @var AnalyticsInterface[]
     */
    private $entities = [];

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->analyticsManager = $this->getContainer()->get('sulu_website.analytics.manager');

        $this->purgeDatabase();
        $this->initEntities();
    }

    public function testListEmptyResponse(): void
    {
        $this->client->jsonRequest('GET', '/api/webspaces/test/analytics');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function testListWithAllDomains(): void
    {
        $this->client->jsonRequest('GET', '/api/webspaces/blog_sulu_io/analytics');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $items = $response['_embedded']['analytics'];
        $this->assertCount(1, $items);
        $this->assertEquals('test piwik', $items[0]['title']);
        $this->assertEquals('matomo', $items[0]['type']);
        $this->assertEquals('123', $items[0]['matomo_id']);
        $this->assertEquals('http://matomo.org', $items[0]['matomo_url']);
        $this->assertEquals(null, $items[0]['domains']);
    }

    public function testList(): void
    {
        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/analytics');

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $items = $response['_embedded']['analytics'];
        $this->assertCount(4, $items);
        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('google', $items[0]['type']);
        $this->assertEquals('UA123-123', $items[0]['google_key']);
        $this->assertCount(1, $items[0]['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $items[0]['domains'][0]);

        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('matomo', $items[1]['type']);
        $this->assertEquals('123', $items[1]['matomo_id']);
        $this->assertCount(1, $items[1]['domains']);
        $this->assertEquals('{country}.test.io', $items[1]['domains'][0]);

        $this->assertEquals('test-3', $items[2]['title']);
        $this->assertEquals('custom', $items[2]['type']);
        $this->assertEquals('<div/>', $items[2]['custom_script']);
        $this->assertEquals('bodyClose', $items[2]['custom_position']);
        $this->assertCount(1, $items[2]['domains']);
        $this->assertEquals('{localization}.google.at', $items[2]['domains'][0]);

        $this->assertEquals('test-4', $items[3]['title']);
        $this->assertEquals('google_tag_manager', $items[3]['type']);
        $this->assertEquals('GTM-XXXX', $items[3]['google_tag_manager_key']);
        $this->assertCount(1, $items[3]['domains']);
        $this->assertEquals('www.sulu.io', $items[3]['domains'][0]);
    }

    public function testGet(): void
    {
        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/analytics/' . $this->entities[0]->getId());

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEquals('test-1', $response['title']);
        $this->assertEquals('google', $response['type']);
        $this->assertEquals('UA123-123', $response['google_key']);
        $this->assertCount(1, $response['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $response['domains'][0]);
    }

    public function testPost(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/webspaces/sulu_io/analytics',
            [
                'title' => 'test-1',
                'type' => 'google',
                'google_key' => 'UA123-123',
                'domains' => ['www.sulu.io/{localization}'],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response['id']);
        $this->assertEquals('test-1', $response['title']);
        $this->assertEquals('google', $response['type']);
        $this->assertEquals('UA123-123', $response['google_key']);
        $this->assertCount(1, $response['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $response['domains'][0]);
    }

    public function testPut(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/webspaces/sulu_io/analytics',
            [
                'title' => 'test-10',
                'type' => 'custom',
                'custom_script' => '<div/>',
                'custom_position' => 'bodyOpen',
                'domains' => ['www.sulu.io'],
            ]
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertNotNull($response['id']);
        $this->assertEquals('test-10', $response['title']);
        $this->assertEquals('custom', $response['type']);
        $this->assertEquals('<div/>', $response['custom_script']);
        $this->assertEquals('bodyOpen', $response['custom_position']);
        $this->assertCount(1, $response['domains']);
        $this->assertEquals('www.sulu.io', $response['domains'][0]);
    }

    public function testDelete(): void
    {
        $this->client->jsonRequest('DELETE', '/api/webspaces/test_io/analytics/' . $this->entities[4]->getId());
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/webspaces/test_io/analytics');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function testDeleteMultiple(): void
    {
        $ids = [
            $this->entities[0]->getId(),
            $this->entities[1]->getId(),
            $this->entities[2]->getId(),
            $this->entities[3]->getId(),
        ];

        $this->client->jsonRequest('DELETE', '/api/webspaces/sulu_io/analytics?ids=' . \implode(',', $ids));
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/webspaces/sulu_io/analytics');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function initEntities(): void
    {
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => ['www.sulu.io/{localization}'],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'matomo',
                'content' => ['siteId' => '123', 'url' => 'http://matomo.org'],
                'domains' => ['{country}.test.io'],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-3',
                'type' => 'custom',
                'content' => [
                    'value' => '<div/>',
                    'position' => 'bodyClose',
                ],
                'domains' => ['{localization}.google.at'],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-4',
                'type' => 'google_tag_manager',
                'content' => 'GTM-XXXX',
                'domains' => ['www.sulu.io'],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'test_io',
            [
                'title' => 'test piwik',
                'type' => 'matomo',
                'content' => ['siteId' => '123', 'url' => 'http://matomo.org'],
                'domains' => ['www.test.io', '{country}.test.io'],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'blog_sulu_io',
            [
                'title' => 'test piwik',
                'type' => 'matomo',
                'content' => ['siteId' => '123', 'url' => 'http://matomo.org'],
                'allDomains' => true,
                'domains' => [],
            ]
        );

        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
