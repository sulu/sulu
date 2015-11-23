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

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class ContentControllerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function setUp()
    {
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
    }

    public function testCGet()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/contents', ['webspace' => 'sulu_io', 'locale' => 'de']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(3, $items);

        $this->assertNotNull($items[0]['uuid']);
        $this->assertEquals('/test-1', $items[0]['path']);
        $this->assertNotNull($items[1]['uuid']);
        $this->assertEquals('/test-2', $items[1]['path']);
        $this->assertNotNull($items[2]['uuid']);
        $this->assertEquals('/test-3', $items[2]['path']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     *
     * @return PageDocument
     */
    private function createPage($title, $locale, $data = [])
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::NONE);
        $document->setShadowLocaleEnabled(false);
        $document->getStructure()->bind($data);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'path' => $this->sessionManager->getContentPath('sulu_io') . '/' . $title,
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        return $document;
    }
}
