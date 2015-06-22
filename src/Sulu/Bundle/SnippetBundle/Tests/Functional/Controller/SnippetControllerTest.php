<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Controller;

use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Content\Compat\StructureInterface;

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

    public function setUp()
    {
        parent::setUp();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->phpcrSession = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->initPhpcr();
        $this->loadFixtures();
        $this->client = $this->createAuthenticatedClient();
    }

    public function testGet()
    {
        $this->client->request('GET', '/snippets/' . $this->hotel1->getUuid() . '?language=de');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);

        $this->assertEquals('Le grande budapest', $res['title']); // snippet nodes do not have a path
        $this->assertEquals($this->hotel1->getUuid(), $res['id']);
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

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);

        $this->assertCount(2, $res['_embedded']['snippets']);
        $res = reset($res['_embedded']['snippets']);
        $this->assertEquals('Le grande budapest', $res['title']);
        $this->assertEquals($this->hotel1->getUuid(), $res['id']);
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

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $res = $res['_embedded']['snippets'];

        $this->assertCount(2, $res);
        $res = reset($res);
        $this->assertEquals('Le grande budapest', $res['title']);
        $this->assertEquals($this->hotel1->getUuid(), $res['id']);
    }

    public function provideIndex()
    {
        return array(
            array(
                array(),
                6,
            ),
            array(
                array(
                    'type' => 'car',
                ),
                4,
            ),
            array(
                array(
                    'type' => 'hotel',
                ),
                2,
            ),
            array(
                array(
                    'limit' => 2,
                    'page' => 1,
                ),
                2,
            ),
            array(
                array(
                    'limit' => 2,
                    'page' => 2,
                ),
                2,
            ),
            array(
                array(
                    'limit' => 2,
                    'page' => 3,
                ),
                2,
            ),
        );
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($params, $expectedNbResults)
    {
        $params = array_merge(array(
            'language' => 'de',
        ), $params);

        $query = http_build_query($params);
        $this->client->request('GET', '/snippets?' . $query);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertCount($expectedNbResults, $res['_embedded']['snippets']);

        foreach ($res['_embedded']['snippets'] as $snippet) {
            // check if all snippets have a title, even if it is a ghost page
            $this->assertArrayHasKey('title', $snippet);
        }
    }

    public function providePost()
    {
        return array(
            array(
                array(),
                array(
                    'template' => 'car',
                    'data' => 'My New Car',
                ),
            ),
            array(
                array(
                    'language' => 'en',
                ),
                array(
                    'template' => 'hotel',
                    'data' => 'Some Hotel Yeah',
                ),
            ),
        );
    }

    /**
     * @dataProvider providePost
     */
    public function testPost($params, $data)
    {
        $params = array_merge(array(
            'language' => 'de',
        ), $params);

        $data = array(
            'template' => 'car',
            'title' => 'My New Car',
        );

        $query = http_build_query($params);
        $this->client->request('POST', '/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertEquals($data['title'], $res['title']);
        $this->assertEquals($params['language'], reset($res['concreteLanguages']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $res['nodeState']);
    }

    /**
     * @dataProvider providePost
     */
    public function testPostPublished($params, $data)
    {
        $params = array_merge(array(
            'language' => 'de',
            'state' => StructureInterface::STATE_PUBLISHED
        ), $params);

        $data = array(
            'template' => 'car',
            'title' => 'My New Car',
        );

        $query = http_build_query($params);
        $this->client->request('POST', '/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertEquals($data['title'], $res['title']);
        $this->assertEquals($params['language'], reset($res['concreteLanguages']));
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $res['nodeState']);
    }

    /**
     * @dataProvider providePost
     */
    public function testPostTest($params, $data)
    {
        $params = array_merge(array(
            'language' => 'de',
            'state' => StructureInterface::STATE_TEST
        ), $params);

        $data = array(
            'template' => 'car',
            'title' => 'My New Car',
        );

        $query = http_build_query($params);
        $this->client->request('POST', '/snippets?' . $query, $data);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertEquals($data['title'], $res['title']);
        $this->assertEquals($params['language'], reset($res['concreteLanguages']));
        $this->assertEquals(StructureInterface::STATE_TEST, $res['nodeState']);
    }

    public function providePut()
    {
        return array(
            array(
                array(),
                array(
                    'template' => 'hotel',
                    'title' => 'Renamed Hotel',
                ),
            ),
        );
    }

    /**
     * @dataProvider providePut
     */
    public function testPut($params, $data)
    {
        $params = array_merge(array(
            'language' => 'de',
        ), $params);

        $query = http_build_query($params);
        $this->client->request('PUT', sprintf('/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertEquals($data['template'], $res['template']);
        $this->assertEquals($data['title'], $res['title']);
        $this->assertEquals($params['language'], reset($res['concreteLanguages']));
    }

    public function testDeleteReferenced()
    {
        $page = $this->documentManager->create('page');
        $page->setStructureType('hotel_page');
        $page->setTitle('Hotels page');
        $page->setResourceSegment('/hotels');
        $page->getStructure()->bind(array('hotels' => array($this->hotel1->getUuid(), $this->hotel2->getUuid())));
        $this->documentManager->persist($page, 'de', array('parent_path' => '/cmf/sulu_io/contents'));
        $this->documentManager->flush();

        $this->client->request('DELETE', '/snippets/' . $this->hotel1->getUuid() . '?_format=text');
        $response = $this->client->getResponse();

        $this->assertEquals(409, $response->getStatusCode());
    }

    private function loadFixtures()
    {
        // HOTELS
        $this->hotel1 = $this->documentManager->create('snippet');
        $this->hotel1->setStructureType('hotel');
        $this->hotel1->setTitle('Le grande budapest');
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
}
