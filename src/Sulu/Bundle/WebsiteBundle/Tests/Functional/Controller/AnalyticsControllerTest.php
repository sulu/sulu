<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\WebsiteBundle\Analytics\AnalyticsManagerInterface;
use Sulu\Bundle\WebsiteBundle\Entity\Analytics;

class AnalyticsControllerTest extends SuluTestCase
{
    /**
     * @var AnalyticsManagerInterface
     */
    private $analyticsManager;

    /**
     * @var Analytics[]
     */
    private $entities = [];

    public function setUp()
    {
        $this->analyticsManager = $this->getContainer()->get('sulu_website.analytics.manager');

        $this->purgeDatabase();
        $this->initEntities();
    }

    public function testListEmptyResponse()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspaces/test/analytics');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspaces/sulu_io/analytics');

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $items = $response['_embedded']['analytics'];
        $this->assertCount(4, $items);
        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('google', $items[0]['type']);
        $this->assertEquals('UA123-123', $items[0]['content']);
        $this->assertCount(1, $items[0]['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $items[0]['domains'][0]['url']);
        $this->assertEquals('prod', $items[0]['domains'][0]['environment']);

        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('piwik', $items[1]['type']);
        $this->assertEquals('123', $items[1]['content']);
        $this->assertCount(2, $items[1]['domains']);
        $this->assertEquals('www.test.io', $items[1]['domains'][0]['url']);
        $this->assertEquals('dev', $items[1]['domains'][0]['environment']);
        $this->assertEquals('{country}.test.io', $items[1]['domains'][1]['url']);
        $this->assertEquals('prod', $items[1]['domains'][1]['environment']);

        $this->assertEquals('test-3', $items[2]['title']);
        $this->assertEquals('custom', $items[2]['type']);
        $this->assertEquals('<div/>', $items[2]['content']);
        $this->assertCount(2, $items[1]['domains']);
        $this->assertEquals('www.google.at', $items[2]['domains'][0]['url']);
        $this->assertEquals('stage', $items[2]['domains'][0]['environment']);
        $this->assertEquals('{localization}.google.at', $items[2]['domains'][1]['url']);
        $this->assertEquals('prod', $items[2]['domains'][1]['environment']);

        $this->assertEquals('test-4', $items[3]['title']);
        $this->assertEquals('google_tag_manager', $items[3]['type']);
        $this->assertEquals('GTM-XXXX', $items[3]['content']);
        $this->assertCount(1, $items[3]['domains']);
        $this->assertEquals('www.sulu.io', $items[3]['domains'][0]['url']);
        $this->assertEquals('prod', $items[3]['domains'][0]['environment']);
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/webspaces/sulu_io/analytics/' . $this->entities[0]->getId());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEquals('test-1', $response['title']);
        $this->assertEquals('google', $response['type']);
        $this->assertEquals('UA123-123', $response['content']);
        $this->assertCount(1, $response['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $response['domains'][0]['url']);
        $this->assertEquals('prod', $response['domains'][0]['environment']);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/webspaces/sulu_io/analytics',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response['id']);
        $this->assertEquals('test-1', $response['title']);
        $this->assertEquals('google', $response['type']);
        $this->assertEquals('UA123-123', $response['content']);
        $this->assertCount(1, $response['domains']);
        $this->assertEquals('www.sulu.io/{localization}', $response['domains'][0]['url']);
        $this->assertEquals('prod', $response['domains'][0]['environment']);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/webspaces/sulu_io/analytics',
            [
                'title' => 'test-10',
                'type' => 'custom',
                'content' => '<div/>',
                'domains' => [
                    ['url' => 'www.sulu.io', 'environment' => 'dev'],
                    ['url' => 'www.sulu.at/{localization}', 'environment' => 'prod'],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertNotNull($response['id']);
        $this->assertEquals('test-10', $response['title']);
        $this->assertEquals('custom', $response['type']);
        $this->assertEquals('<div/>', $response['content']);
        $this->assertCount(2, $response['domains']);
        $this->assertEquals('www.sulu.io', $response['domains'][0]['url']);
        $this->assertEquals('dev', $response['domains'][0]['environment']);
        $this->assertEquals('www.sulu.at/{localization}', $response['domains'][1]['url']);
        $this->assertEquals('prod', $response['domains'][1]['environment']);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('DELETE', '/api/webspaces/test_io/analytics/' . $this->entities[4]->getId());
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/webspaces/test_io/analytics');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function testDeleteMultiple()
    {
        $client = $this->createAuthenticatedClient();

        $ids = [
            $this->entities[0]->getId(),
            $this->entities[1]->getId(),
            $this->entities[2]->getId(),
            $this->entities[3]->getId(),
        ];

        $client->request('DELETE', '/api/webspaces/sulu_io/analytics?ids=' . implode(',', $ids));
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/webspaces/sulu_io/analytics');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertEmpty($response['_embedded']['analytics']);
    }

    public function initEntities()
    {
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-1',
                'type' => 'google',
                'content' => 'UA123-123',
                'domains' => [['url' => 'www.sulu.io/{localization}', 'environment' => 'prod']],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-2',
                'type' => 'piwik',
                'content' => '123',
                'domains' => [
                    ['url' => 'www.test.io', 'environment' => 'dev'],
                    ['url' => '{country}.test.io', 'environment' => 'prod'],
                ],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-3',
                'type' => 'custom',
                'content' => '<div/>',
                'domains' => [
                    ['url' => 'www.google.at', 'environment' => 'stage'],
                    ['url' => '{localization}.google.at', 'environment' => 'prod'],
                ],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'sulu_io',
            [
                'title' => 'test-4',
                'type' => 'google_tag_manager',
                'content' => 'GTM-XXXX',
                'domains' => [['url' => 'www.sulu.io', 'environment' => 'prod']],
            ]
        );
        $this->entities[] = $this->analyticsManager->create(
            'test_io',
            [
                'title' => 'test piwik',
                'type' => 'piwik',
                'content' => '123',
                'domains' => [
                    ['url' => 'www.test.io', 'environment' => 'dev'],
                    ['url' => '{country}.test.io', 'environment' => 'prod'],
                ],
            ]
        );

        $this->getContainer()->get('doctrine.orm.entity_manager')->flush();
    }
}
