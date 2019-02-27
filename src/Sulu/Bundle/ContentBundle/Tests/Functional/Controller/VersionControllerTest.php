<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class VersionControllerTest extends SuluTestCase
{
    public function setUp()
    {
        if (!$this->getContainer()->getParameter('sulu_document_manager.versioning.enabled')) {
            $this->markTestSkipped('Versioning is not enabled');
        }

        $this->initPhpcr();
    }

    public function testPostRestore()
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $document = $documentManager->create('page');
        $document->setTitle('first title');
        $document->setStructureType('default');
        $document->setResourceSegment('/first-title');
        $documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $document = $documentManager->find($document->getUuid(), 'de');
        $document->setTitle('second title');
        $documentManager->persist($document, 'de');
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/nodes/' . $document->getUuid() . '/versions/1_0?action=restore&language=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('first title', $response['title']);
    }

    public function testPostRestoreInvalidVersion()
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $document = $documentManager->create('page');
        $document->setTitle('first title');
        $document->setStructureType('default');
        $document->setResourceSegment('/first-title');
        $documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/nodes/' . $document->getUuid() . '/versions/2_0?action=restore&language=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testCGet()
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $document = $documentManager->create('page');
        $document->setTitle('first title');
        $document->setStructureType('default');
        $document->setResourceSegment('/first-title');
        $documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $document = $documentManager->find($document->getUuid(), 'de');
        $document->setTitle('second title');
        $documentManager->persist($document, 'de');
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes/' . $document->getUuid() . '/versions?language=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);

        $versions = $response['_embedded']['versions'];
        $this->assertEquals('1_1', $versions[0]['id']);
        $this->assertEquals('de', $versions[0]['locale']);
        $this->assertEquals('1_0', $versions[1]['id']);
        $this->assertEquals('de', $versions[1]['locale']);
    }
}
