<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PageResourcelocatorControllerTest extends SuluTestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var EntityRepository<Activity>
     */
    private $activityRepository;

    /**
     * @var array
     */
    private $data;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->session = $this->getContainer()->get('doctrine')->getConnection();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->data = $this->prepareRepositoryContent();
        $this->activityRepository = $this->getContainer()->get('sulu.repository.activity');
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&locale=en&action=publish',
            $data[0]
        );
        $data[0] = (array) \json_decode($this->client->getResponse()->getContent(), true);
        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&locale=en&action=publish',
            $data[1]
        );
        $data[1] = (array) \json_decode($this->client->getResponse()->getContent(), true);
        $this->client->jsonRequest('POST', '/api/pages?webspace=sulu_io&locale=en&action=publish&parentId=' . $data[1]['id'], $data[2]);
        $data[2] = (array) \json_decode($this->client->getResponse()->getContent(), true);
        $this->client->jsonRequest('POST', '/api/pages?webspace=sulu_io&locale=en&action=publish&parentId=' . $data[1]['id'], $data[3]);
        $data[3] = (array) \json_decode($this->client->getResponse()->getContent(), true);
        $this->client->jsonRequest('POST', '/api/pages?webspace=sulu_io&locale=en&action=publish&parentId=' . $data[3]['id'], $data[4]);
        $data[4] = (array) \json_decode($this->client->getResponse()->getContent(), true);

        return $data;
    }

    public function testGenerate(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?webspace=sulu_io&locale=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?parent=' . $this->data[0]['id'] . '&webspace=sulu_io&locale=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/products/test', $response->resourceLocator);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?parent=' . $this->data[1]['id'] . '&webspace=sulu_io&locale=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-2', $response->resourceLocator);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?parent=' . $this->data[3]['id'] . '&webspace=sulu_io&locale=en&template=default',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/news/test-1/test-1', $response->resourceLocator);
    }

    public function testGenerateWithIncompleteParts(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?webspace=sulu_io&locale=en&template=overview',
            ['parts' => ['title' => 'test1', 'subtitle' => 'test2']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test2-test1', $response->resourceLocator);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?webspace=sulu_io&locale=en&template=overview',
            ['parts' => ['title' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/resourcelocators/generates?webspace=sulu_io&locale=en&template=overview',
            ['parts' => ['subtitle' => 'test']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('/test', $response->resourceLocator);
    }

    public function testGetAction(): void
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $newsData['id'] . '?webspace=sulu_io&locale=en&action=publish',
            $newsData
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $newsData = (array) \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&locale=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = (array) \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(1, \count($result['_embedded']['page_resourcelocators']));
        $this->assertEquals(1, $result['total']);
        $this->assertEquals('/news', $result['_embedded']['page_resourcelocators'][0]['resourcelocator']);
    }

    public function testDelete(): void
    {
        // prepare history nodes
        $newsData = $this->data[1];
        $newsData['url'] = '/test';
        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $newsData['id'] . '?webspace=sulu_io&locale=en&action=publish',
            $newsData
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $newsData = (array) \json_decode($this->client->getResponse()->getContent());

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&locale=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $history = (array) \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'DELETE',
            \sprintf(
                '/api/pages/%s/resourcelocators?ids=%s&webspace=sulu_io&locale=en',
                $newsData['id'],
                $history['_embedded']['page_resourcelocators'][0]['id']
            )
        );
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        self::assertNotNull($this->activityRepository->findOneBy(['type' => 'route_removed', 'resourceKey' => BasePageDocument::RESOURCE_KEY, 'resourceId' => $newsData['id']]));

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $newsData['id'] . '/resourcelocators?webspace=sulu_io&locale=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = (array) \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(0, \count($result['_embedded']['page_resourcelocators']));
        $this->assertEquals(0, $result['total']);
    }
}
