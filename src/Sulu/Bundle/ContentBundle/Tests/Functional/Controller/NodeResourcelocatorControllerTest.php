<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

/**
 * @group webtest
 */
class NodeResourcelocatorControllerTest extends SuluTestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    private $data;

    protected function setUp()
    {
        $this->session = $this->getContainer()->get('doctrine')->getConnection();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->data = $this->prepareRepositoryContent();
        $this->client = $this->createClient(
            [],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
    }

    private function prepareRepositoryContent()
    {
        $data = [
            [
                'title' => 'Produkte',
                'template' => 'default',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/products',
                'article' => 'Test',
            ],
            [
                'title' => 'News',
                'template' => 'default',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news',
                'article' => 'Test',
            ],
            [
                'title' => 'test',
                'template' => 'default',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test',
                'article' => 'Test',
            ],
            [
                'title' => 'test-2',
                'template' => 'default',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-1',
                'article' => 'Test',
            ],
            [
                'title' => 'test',
                'template' => 'default',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-1/test',
                'article' => 'Test',
            ],
        ];

        $client = $this->createClient(
            [],
            [
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            ]
        );
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&action=publish', $data[0]);
        $data[0] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&action=publish', $data[1]);
        $data[1] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&action=publish&parent=' . $data[1]['id'], $data[2]);
        $data[2] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&action=publish&parent=' . $data[1]['id'], $data[3]);
        $data[3] = (array) json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&action=publish&parent=' . $data[3]['id'], $data[4]);
        $data[4] = (array) json_decode($client->getResponse()->getContent(), true);

        return $data;
    }

    public function testGenerate()
    {
        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?webspace=sulu_io&language=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[0]['id'] . '&webspace=sulu_io&language=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/products/test', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[1]['id'] . '&webspace=sulu_io&language=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-2', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?parent=' . $this->data[3]['id'] . '&webspace=sulu_io&language=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-1/test-1', $response->resourceLocator);
    }

    public function testGenerateWithIncompleteParts()
    {
        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?webspace=sulu_io&language=en&template=overview',
            ['parts' => ['title' => 'test1', 'subtitle' => 'test2']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test2-test1', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?webspace=sulu_io&language=en&template=overview',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->request(
            'POST',
            '/api/nodes/resourcelocators/generates?webspace=sulu_io&language=en&template=overview',
            ['parts' => ['subtitle' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);
    }

    public function testGetAction()
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en&action=publish',
            $newsData
        );
        $newsData = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, count($result['_embedded']['resourcelocators']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('/news', $result['_embedded']['resourcelocators'][0]['resourceLocator']);
    }

    public function testDelete()
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/nodes/' . $newsData['id'] . '?webspace=sulu_io&language=en&action=publish',
            $newsData
        );
        $newsData = (array) json_decode($this->client->getResponse()->getContent());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $history = (array) json_decode($this->client->getResponse()->getContent(), true);

        $url = $history['_embedded']['resourcelocators'][0]['_links']['delete'];

        $url = substr($url, 6);
        $this->client->request('DELETE', $url);
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request(
            'GET',
            '/api/nodes/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = (array) json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(0, count($result['_embedded']['resourcelocators']));
        $this->assertEquals(0, $result['total']);
    }
}
