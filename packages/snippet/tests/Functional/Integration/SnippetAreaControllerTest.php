<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Functional\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\TestBundle\Testing\AssertSnapshotTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * The integration test should have no impact on the coverage so we set it to coversNothing.
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class SnippetAreaControllerTest extends SuluTestCase
{
    use AssertSnapshotTrait;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient(
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json']
        );

        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($classes, false);
    }

    public function testGetList(): string
    {
        self::purgeDatabase();

        $this->client->jsonRequest('GET', '/admin/api/snippet-areas/sulu-io');

        $this->assertResponseSnapshot('snippet_area_cget.json', $this->client->getResponse(), 200);

        self::ensureKernelShutdown();
    }

    //public function testPut(): void
    //{
    //$this->client->jsonRequest(
    //'PUT',
    //'/admin/api/snippet-areas/car',
    //['webspace' => 'sulu_io', 'defaultUuid' => $this->car1->getUuid()]
    //);

    //$this->assertHttpStatusCode(200, $this->client->getResponse());
    //$response = \json_decode($this->client->getResponse()->getContent(), true);

    //$this->assertEquals('car', $response['template']);
    //$this->assertEquals('Car', $response['title']);
    //$this->assertEquals($this->car1->getUuid(), $response['defaultUuid']);
    //$this->assertEquals($this->car1->getTitle(), $response['defaultTitle']);

    //$this->client->jsonRequest('GET', '/api/snippet-areas?webspace=sulu_io');

    //$this->assertHttpStatusCode(200, $this->client->getResponse());
    //$response = \json_decode($this->client->getResponse()->getContent(), true);
    //$data = $response['_embedded']['areas'];

    //$this->assertEquals(3, $response['total']);
    //$this->assertEquals('car', $data[0]['template']);
    //$this->assertEquals('Car', $data[0]['title']);
    //$this->assertEquals($this->car1->getTitle(), $data[0]['defaultTitle']);
    //$this->assertEquals($this->car1->getUuid(), $data[0]['defaultUuid']);
    //$this->assertEquals('hotel', $data[1]['template']);
    //$this->assertEquals('Golf hotel', $data[1]['title']);
    //$this->assertEquals(null, $data[1]['defaultTitle']);
    //$this->assertEquals(null, $data[1]['defaultUuid']);
    //$this->assertEquals('hotel', $data[2]['template']);
    //$this->assertEquals('Sport hotel', $data[2]['title']);
    //$this->assertEquals(null, $data[2]['defaultTitle']);
    //$this->assertEquals(null, $data[2]['defaultUuid']);
    //}

    //#[\PHPUnit\Framework\Attributes\Depends('testPut')]
    //public function testDelete(): void
    //{
    //$this->client->jsonRequest(
    //'DELETE',
    //'/api/snippet-areas/car',
    //['webspace' => 'sulu_io']
    //);

    //$this->assertHttpStatusCode(200, $this->client->getResponse());
    //$response = \json_decode($this->client->getResponse()->getContent(), true);

    //$this->assertEquals('car', $response['template']);
    //$this->assertEquals('Car', $response['title']);
    //$this->assertEquals(null, $response['defaultUuid']);
    //$this->assertEquals(null, $response['defaultTitle']);

    //$this->client->jsonRequest('GET', '/api/snippet-areas?webspace=sulu_io');

    //$this->assertHttpStatusCode(200, $this->client->getResponse());
    //$response = \json_decode($this->client->getResponse()->getContent(), true);
    //$data = $response['_embedded']['areas'];

    //$this->assertEquals(3, $response['total']);
    //$this->assertEquals('car', $data[0]['template']);
    //$this->assertEquals('Car', $data[0]['title']);
    //$this->assertEquals(null, $data[0]['defaultTitle']);
    //$this->assertEquals(null, $data[0]['defaultUuid']);
    //$this->assertEquals('hotel', $data[1]['template']);
    //$this->assertEquals('Golf hotel', $data[1]['title']);
    //$this->assertEquals(null, $data[1]['defaultTitle']);
    //$this->assertEquals(null, $data[1]['defaultUuid']);
    //$this->assertEquals('hotel', $data[2]['template']);
    //$this->assertEquals('Sport hotel', $data[2]['title']);
    //$this->assertEquals(null, $data[2]['defaultTitle']);
    //$this->assertEquals(null, $data[2]['defaultUuid']);
    //}

    protected function getSnapshotFolder(): string
    {
        return 'responses';
    }
}
