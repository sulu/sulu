<?php

namespace Sulu\Bundle\SnippetBundle\Tests\Integration;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

class SnippetApiTest extends SuluTestCase
{
    protected $client;
    protected $hotel1;
    protected $contentMapper;

    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();
        $this->client = $this->createClient(
            array(),
            array(
                'PHP_AUTH_USER' => 'test',
                'PHP_AUTH_PW' => 'test',
            )
        );
    }

    public function testGet()
    {
        $this->client->request('GET', '/api/snippets/' . $this->hotel1->getUuid() . '?language=de&webspace=sulu_io');
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);

        $this->assertEquals('/cmf/snippets/hotel/le-grande-budapest', $res['path']);
        $this->assertEquals($this->hotel1->getUuid(), $res['id']);
    }

    public function provideIndex()
    {
        return array(
            array(
                array(),
                5
            ),
            array(
                array(
                    'type' => 'car',
                ),
                3
            ),
            array(
                array(
                    'type' => 'hotel',
                ),
                2
            ),
            array(
                array(
                    'type' => 'car',
                    'offset' => 1,
                    'max' => 1
                ),
                1
            ),
        );
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($params, $expectedNbResults)
    {
        $params = array_merge(array(
            'webspace' => 'sulu_io',
            'language' => 'de',
        ), $params);

        $query = http_build_query($params);
        $this->client->request('GET', '/api/snippets?' . $query);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertCount($expectedNbResults, $res);

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
            )
        );

    }

    /**
     * @dataProvider providePost
     */
    public function testPost($params, $data)
    {
        $params = array_merge(array(
            'webspace' => 'sulu_io',
            'language' => 'de',
        ), $params);

        $data = array(
            'template' => 'car',
            'title' => 'My New Car',
        );

        $query = http_build_query($params);
        $this->client->request('POST', '/api/snippets?' . $query, $data);
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
            'webspace' => 'sulu_io',
            'language' => 'de',
        ), $params);

        $query = http_build_query($params);
        $this->client->request('PUT', sprintf('/api/snippets/%s?%s', $this->hotel1->getUuid(), $query), $data);
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $res = json_decode($response->getContent(), true);
        $this->assertEquals($data['template'], $res['template']);
        $this->assertEquals($data['title'], $res['title']);
        $this->assertEquals($params['language'], reset($res['concreteLanguages']));
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
                'title' => 'Le grande budapest'
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
        $this->contentMapper->saveRequest($req);

        // CARS
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Skoda'
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
