<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use PHPCR\SessionInterface;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Client;

class SnippetControllerTest extends SuluTestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var SnippetDocument
     */
    protected $hotel1;

    /**
     * @var SnippetDocument
     */
    protected $hotel2;

    /**
     * @var DocumentManagerInterface
     */
    protected $documentManager;

    /**
     * @var SessionInterface
     */
    private $phpcrSession;

    public function setUp()
    {
        parent::setUp();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->phpcrSession = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->initPhpcr();
        $this->loadFixtures();
        $this->client = $this->createAuthenticatedClient();
    }

    /**
     * @dataProvider provideGet
     */
    public function testGet($locale, $expected)
    {
        $this->client->request('GET', '/snippets/' . $this->hotel1->getUuid() . '?language=' . $locale);
        $response = $this->client->getResponse();

        $result = $response->getContent();
        $result = json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);
        $this->assertLinks([
            'self', 'delete', 'update', 'new',
        ], $result);

        $this->assertEquals($expected['title'], $result['title']);
        $this->assertEquals($expected['description'], $result['description']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
    }

    public function provideGet()
    {
        return [
            [
                'de',
                [
                    'title' => 'Das Großes Budapest',
                    'description' => 'Hallo Weld!',
                ],
            ],
            [
                'en',
                [
                    'title' => 'The Grand Budapest',
                    'description' => 'Hello World',
                ],
            ],
        ];
    }

    public function testGetMany()
    {
        $this->client->request('GET', sprintf(
            '/snippets?ids=%s,%s%s',
            $this->hotel1->getUuid(),
            $this->hotel2->getUuid(),
            '&language=de'
        ));
        $response = $this->client->getResponse();

        $result = json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);
        $this->assertLinks([
            'self', 'first', 'last', 'filter', 'find', 'pagination', 'sortable',
        ], $result);

        $this->assertCount(2, $result['_embedded']['snippets']);

        $result = reset($result['_embedded']['snippets']);
        $this->assertEquals('Das Großes Budapest', $result['title']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
        $this->assertEquals('Hotel', $result['localizedTemplate']);
    }

    public function testGetManyLocalizedTemplate()
    {
        $this->client->request(
            'GET',
            sprintf(
                '/snippets?ids=%s&language=fr',
                $this->hotel1->getUuid()
            )
        );
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);
        $result = reset($result['_embedded']['snippets']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
        $this->assertEquals('Hôtel', $result['localizedTemplate']);
    }

    public function testGetMultipleWithNotExistingIds()
    {
        $this->client->request('GET', sprintf(
            '/snippets?ids=%s,%s,%s%s',
            $this->hotel1->getUuid(),
            '99999999-754c-4da0-bbc7-bf909b05c352',
            $this->hotel2->getUuid(),
            '&language=de'
        ));
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);
        $result = $result['_embedded']['snippets'];
        $this->assertCount(2, $result);
        $result = reset($result);
        $this->assertEquals('Das Großes Budapest', $result['title']);
        $this->assertEquals($this->hotel1->getUuid(), $result['id']);
    }

    public function provideIndex()
    {
        return [
            [
                [],
                6,
            ],
            [
                [
                    'type' => 'car',
                ],
                4,
            ],
            [
                [
                    'type' => 'hotel',
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 1,
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 2,
                ],
                2,
            ],
            [
                [
                    'limit' => 2,
                    'page' => 3,
                ],
                2,
            ],
        ];
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($params, $expectedNbResults)
    {
        $params = array_merge([
            'language' => 'de',
        ], $params);

        $query = http_build_query($params);
        $this->client->request('GET', '/snippets?' . $query);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);

        $result = json_decode($response->getContent(), true);
        $this->assertCount($expectedNbResults, $result['_embedded']['snippets']);

        foreach ($result['_embedded']['snippets'] as $snippet) {
            // check if all snippets have a title, even if it is a ghost page
            $this->assertArrayHasKey('title', $snippet);
        }
    }

    public function providePost()
    {
        return [
            [
                [],
                [
                    'template' => 'car',
                    'data' => 'My New Car',
                ],
            ],
            [
                [
                    'language' => 'en',
                ],
                [
                    'template' => 'hotel',
                    'data' => 'Some Hotel Yeah',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePost
     */
    public function testPost($params, $data)
    {
        $params = array_merge([
            'language' => 'de',
        ], $params);

        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $query = http_build_query($params);
        $this->client->request('POST', '/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $result = json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);
        $this->assertLinks([
            'self', 'delete', 'update', 'new',
        ], $result);

        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($params['language'], reset($result['concreteLanguages']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
        $this->assertEquals('My car is red.', $result['description']);

        try {
            $this->documentManager->find($result['id'], 'de');
        } catch (\DocumentNotFoundException $e) {
            $this->fail('Document was not persisted');
        }
    }

    /**
     * @dataProvider providePost
     */
    public function testPostPublished($params, $data)
    {
        $params = array_merge([
            'language' => 'de',
            'state' => StructureInterface::STATE_PUBLISHED,
        ], $params);

        $data = [
            'template' => 'car',
            'title' => 'My New Car',
        ];

        $query = http_build_query($params);
        $this->client->request('POST', '/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($params['language'], reset($result['concreteLanguages']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
    }

    public function testPut()
    {
        $data = [
            'template' => 'hotel',
            'title' => 'Renamed Hotel',
            'description' => 'My hotel is red',
        ];

        $params = [
            'language' => 'de',
        ];

        $query = http_build_query($params);
        $this->client->request('PUT', sprintf('/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $result = json_decode($response->getContent(), true);
        $this->assertHttpStatusCode(200, $response);

        foreach ($data as $key => $value) {
            $this->assertEquals($data[$key], $value);
        }

        $this->assertContains($params['language'], $result['concreteLanguages']);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);

        $document = $this->documentManager->find($result['id'], 'de');

        $this->assertEquals($data['title'], $document->getTitle());
        $this->assertEquals($data['description'], $document->getStructure()->getProperty('description')->getValue());
    }

    public function testPutPublished()
    {
        $data = [];
        $params = [
            'language' => 'de',
            'state' => StructureInterface::STATE_PUBLISHED,
        ];

        $query = http_build_query($params);
        $this->client->request('PUT', sprintf('/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);
        $this->assertLinks([
            'self', 'delete', 'update', 'new',
        ], $result);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $result[$key]);
        }

        $this->assertContains($params['language'], $result['concreteLanguages']);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $result['nodeState']);
    }

    public function testPutWithValidHash()
    {
        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $this->client->request('POST', '/snippets?language=de', $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);

        $this->client->request(
            'PUT',
            '/snippets/' . $result['id'] . '?language=de',
            array_merge(['_hash' => $result['_hash']], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithInvalidHash()
    {
        $data = [
            'template' => 'car',
            'title' => 'My New Car',
            'description' => 'My car is red.',
        ];

        $this->client->request('POST', '/snippets?language=de', $data);
        $response = $this->client->getResponse();

        $this->assertHttpStatusCode(200, $response);
        $result = json_decode($response->getContent(), true);

        $this->client->request(
            'PUT',
            '/snippets/' . $result['id'] . '?language=de',
            array_merge(['_hash' => 'wrong-hash'], $data)
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testDeleteReferenced()
    {
        $page = $this->documentManager->create('page');
        $page->setStructureType('hotel_page');
        $page->setTitle('Hotels page');
        $page->setResourceSegment('/hotels');
        $page->getStructure()->bind(['hotels' => [$this->hotel1->getUuid(), $this->hotel2->getUuid()]]);
        $this->documentManager->persist($page, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('DELETE', '/snippets/' . $this->hotel1->getUuid() . '?_format=text');
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals($page->getUuid(), $content['structures'][0]['id']);
    }

    public function testDeleteReferencedOther()
    {
        $node = $this->phpcrSession->getRootNode()->addNode('test-other');
        $node->setProperty('test', $this->phpcrSession->getNodeByIdentifier($this->hotel1->getUuid()));
        $this->phpcrSession->save();

        $this->client->request('DELETE', '/snippets/' . $this->hotel1->getUuid() . '?_format=text');
        $response = $this->client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertHttpStatusCode(409, $response);
        $this->assertEquals($node->getPath(), $content['other'][0]);
    }

    public function testCopyLocale()
    {
        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('hotel');
        $snippet->setTitle('Hotel de');
        $this->documentManager->persist($snippet, 'de');
        $this->documentManager->flush();

        $this->client->request('POST', '/snippets/' . $snippet->getUuid() . '?action=copy-locale&dest=en&language=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $newPage = $this->documentManager->find($snippet->getUuid(), 'en');
        $this->assertEquals('Hotel de', $newPage->getTitle());

        $newPage = $this->documentManager->find($snippet->getUuid(), 'de');
        $this->assertEquals('Hotel de', $newPage->getTitle());
    }

    public function testGetFields()
    {
        $this->client->request('GET', '/snippet/fields');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $body = $response->getContent();
        $fields = json_decode($body);
        $this->assertNotNull($fields);
    }

    private function loadFixtures()
    {
        // HOTELS
        $this->hotel1 = $this->documentManager->create('snippet');
        $this->hotel1->setStructureType('hotel');
        $this->hotel1->setTitle('The Grand Budapest');
        $this->hotel1->getStructure()->getProperty('description')->setValue('Hello World');
        $this->documentManager->persist($this->hotel1, 'en');

        $this->hotel1->getStructure()->getProperty('description')->setValue('Hallo Weld!');
        $this->hotel1->setTitle('Das Großes Budapest');
        $this->documentManager->persist($this->hotel1, 'de');

        $this->hotel2 = $this->documentManager->create('snippet');
        $this->hotel2->setStructureType('hotel');
        $this->hotel2->setTitle('L\'Hôtel New Hampshire');
        $this->documentManager->persist($this->hotel2, 'de');

        // CARS
        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Skoda');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Volvo');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('Ford');
        $this->documentManager->persist($car, 'de');

        $car = $this->documentManager->create('snippet');
        $car->setStructureType('car');
        $car->setTitle('VW');
        $this->documentManager->persist($car, 'en');

        $this->documentManager->flush();
    }

    private function assertLinks(array $links, array $response)
    {
        $this->assertArrayHasKey('_links', $response);
        $responseLinks = $response['_links'];

        $diff = array_diff($links, array_keys($responseLinks));

        if ($diff) {
            $this->fail(sprintf(
                'Failed asserting that the links "%s" were present in response. Got "%s"',
                implode('", "', $diff), implode('", "', array_keys($responseLinks))
            ));
        }
    }
}
