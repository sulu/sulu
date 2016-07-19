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

use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

/**
 * Handles snippet types and defaults.
 */
class SnippetTypesControllerTest extends BaseFunctionalTestCase
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

        $client->request('GET', '/snippet-types');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $response['_embedded'][0]['template']);
        $this->assertEquals('Car', $response['_embedded'][0]['title']);
        $this->assertArrayNotHasKey('defaultTitle', $response['_embedded'][0]);
        $this->assertArrayNotHasKey('defaultUuid', $response['_embedded'][0]);
        $this->assertEquals('hotel', $response['_embedded'][1]['template']);
        $this->assertEquals('Hotel', $response['_embedded'][1]['title']);
        $this->assertArrayNotHasKey('defaultTitle', $response['_embedded'][1]);
        $this->assertArrayNotHasKey('defaultUuid', $response['_embedded'][1]);
    }

    public function testPutDefault()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/snippet-types/car/default',
            ['webspace' => 'sulu_io', 'default' => $this->car1->getUuid()]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals($this->car1->getUuid(), $response['defaultUuid']);
        $this->assertEquals($this->car1->getTitle(), $response['defaultTitle']);

        $client->request('GET', '/snippet-types?defaults=true&webspace=sulu_io');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $response['_embedded'][0]['template']);
        $this->assertEquals('Car', $response['_embedded'][0]['title']);
        $this->assertEquals($this->car1->getTitle(), $response['_embedded'][0]['defaultTitle']);
        $this->assertEquals($this->car1->getUuid(), $response['_embedded'][0]['defaultUuid']);
        $this->assertEquals('hotel', $response['_embedded'][1]['template']);
        $this->assertEquals('Hotel', $response['_embedded'][1]['title']);
        $this->assertEquals(null, $response['_embedded'][1]['defaultTitle']);
        $this->assertEquals(null, $response['_embedded'][1]['defaultUuid']);
    }

    /**
     * @depends testPutDefault
     */
    public function testDeleteDefault()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'DELETE',
            '/snippet-types/car/default',
            ['webspace' => 'sulu_io', 'default' => $this->car1->getUuid()]
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('car', $response['template']);
        $this->assertEquals('Car', $response['title']);
        $this->assertEquals(null, $response['defaultUuid']);
        $this->assertEquals(null, $response['defaultTitle']);

        $client->request('GET', '/snippet-types?defaults=true&webspace=sulu_io');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);
        $this->assertEquals('car', $response['_embedded'][0]['template']);
        $this->assertEquals('Car', $response['_embedded'][0]['title']);
        $this->assertEquals(null, $response['_embedded'][0]['defaultTitle']);
        $this->assertEquals(null, $response['_embedded'][0]['defaultUuid']);
        $this->assertEquals('hotel', $response['_embedded'][1]['template']);
        $this->assertEquals('Hotel', $response['_embedded'][1]['title']);
        $this->assertEquals(null, $response['_embedded'][1]['defaultTitle']);
        $this->assertEquals(null, $response['_embedded'][1]['defaultUuid']);
    }
}
