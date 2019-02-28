<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * Handles snippet types and defaults.
 */
class SnippetAreaControllerTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();
    }

    public function testCGet()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/snippet-areas?webspace=sulu_io');
        $response = json_decode($client->getResponse()->getContent(), true);

        $data = $response['_embedded']['areas'];
        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals(null, $data[0]['defaultTitle']);
        $this->assertEquals(null, $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
    }

    public function testPutDefault()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/snippet-areas/car',
            ['webspace' => 'sulu_io', 'default' => $this->car1->getUuid()]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals($this->car1->getUuid(), $response['defaultUuid']);
        $this->assertEquals($this->car1->getTitle(), $response['defaultTitle']);

        $client->request('GET', '/snippet-areas?webspace=sulu_io');
        $response = json_decode($client->getResponse()->getContent(), true);
        $data = $response['_embedded']['areas'];

        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals($this->car1->getTitle(), $data[0]['defaultTitle']);
        $this->assertEquals($this->car1->getUuid(), $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
    }

    /**
     * @depends testPutDefault
     */
    public function testDeleteDefault()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/snippet-areas/car',
            ['webspace' => 'sulu_io', 'default' => $this->car1->getUuid()]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals(null, $response['defaultUuid']);
        $this->assertEquals(null, $response['defaultTitle']);

        $client->request('GET', '/snippet-areas?webspace=sulu_io');

        $response = json_decode($client->getResponse()->getContent(), true);
        $data = $response['_embedded']['areas'];

        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $data[0]['template']);
        $this->assertEquals('Car', $data[0]['title']);
        $this->assertEquals(null, $data[0]['defaultTitle']);
        $this->assertEquals(null, $data[0]['defaultUuid']);
        $this->assertEquals('hotel', $data[1]['template']);
        $this->assertEquals('Hotel', $data[1]['title']);
        $this->assertEquals(null, $data[1]['defaultTitle']);
        $this->assertEquals(null, $data[1]['defaultUuid']);
    }
}
