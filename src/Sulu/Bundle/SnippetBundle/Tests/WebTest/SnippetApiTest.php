<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Integration;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Structure\Snippet;
use Symfony\Bundle\FrameworkBundle\Client;

class SnippetApiTest extends SuluTestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var Snippet
     */
    protected $hotel1;

    /**
     * @var Snippet
     */
    protected $hotel2;

    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    public function setUp()
    {
        parent::setUp();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
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
                5,
            ),
            array(
                array(
                    'type' => 'car',
                ),
                3,
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
                1,
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
        $request = ContentMapperRequest::create()
            ->setType('page')
            ->setWebspaceKey('sulu_io')
            ->setTemplateKey('hotel_page')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Hotels page',
                'url' => '/hotels',
                'hotels' => array(
                    $this->hotel1->getUuid(),
                    $this->hotel2->getUuid(),
                ),
            ));

        $this->contentMapper->saveRequest($request);
        $this->client->request('DELETE', '/snippets/' . $this->hotel1->getUuid() . '?_format=text');
        $response = $this->client->getResponse();

        $this->assertEquals(409, $response->getStatusCode());
    }

    private function loadFixtures()
    {
        // HOTELS
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Le grande budapest',
            ));
        $this->hotel1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'L\'Hôtel New Hampshire',
            ));
        $this->hotel2 = $this->contentMapper->saveRequest($req);

        // CARS
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Skoda',
            ));
        $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Volvo',
            ));
        $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Ford',
            ));
        $this->contentMapper->saveRequest($req);
    }
}
