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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class VersionControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();

        if (!$this->getContainer()->getParameter('sulu_document_manager.versioning.enabled')) {
            // If versioning is disabled controller should not be available
            $this->assertFalse($this->getContainer()->has('sulu_page.version_controller'));

            $this->markTestSkipped('Versioning is not enabled');
        }

        $this->initPhpcr();
    }

    public function testPostRestore(): void
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

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $document->getUuid() . '/versions/1_0?action=restore&locale=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('first title', $response['title']);
    }

    public function testPostRestoreInvalidVersion(): void
    {
        $documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $document = $documentManager->create('page');
        $document->setTitle('first title');
        $document->setStructureType('default');
        $document->setResourceSegment('/first-title');
        $documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $documentManager->publish($document, 'de');
        $documentManager->flush();

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $document->getUuid() . '/versions/2_0?action=restore&locale=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCGet(): void
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

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $document->getUuid() . '/versions?locale=de&webspace=sulu_io'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(2, $response['total']);

        $versions = $response['_embedded']['page_versions'];
        $this->assertEquals('1_1', $versions[0]['id']);
        $this->assertEquals('de', $versions[0]['locale']);
        $this->assertEquals('1_0', $versions[1]['id']);
        $this->assertEquals('de', $versions[1]['locale']);
    }
}
