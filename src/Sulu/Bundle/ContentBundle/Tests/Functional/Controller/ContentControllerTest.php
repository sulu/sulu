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

    public function testCGetWithShadow()
    {
        $this->initPhpcr();

        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/contents', ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeShadow()
    {
        $this->initPhpcr();

        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/contents',
            ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title', 'exclude-shadows' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhost()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/contents', ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhost()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/contents',
            ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title', 'exclude-ghosts' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhostAndShadow()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/contents', ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhostAndShadow()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/contents',
            ['webspace' => 'sulu_io', 'locale' => 'de', 'mapping' => 'title', 'exclude-ghosts' => true, 'exclude-shadows' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['content'];

        $this->assertCount(1, $items);

        $this->assertEquals('test-3', $items[0]['title']);
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

    /**
     * @param string $title
     * @param string $locale
     * @param string $shadowedLocale
     *
     * @return PageDocument
     */
    private function createShadowPage($title, $locale, $shadowedLocale)
    {
        $document1 = $this->createPage($title, $locale);
        $document = $this->documentManager->find(
            $document1->getUuid(),
            $shadowedLocale,
            ['load_ghost_content' => false]
        );

        $document->setShadowLocaleEnabled(true);
        $document->setTitle(strrev($title));
        $document->setShadowLocale($locale);
        $document->setLocale($shadowedLocale);
        $document->setResourceSegment($document1->getResourceSegment());

        $this->documentManager->persist($document, $shadowedLocale);
        $this->documentManager->flush();

        return $document;
    }

    /**
     * @param string $title
     * @param string $locale
     * @param PageDocument $link
     *
     * @return PageDocument
     */
    private function createInternalLinkPage($title, $locale, PageDocument $link)
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::INTERNAL);
        $document->setRedirectTarget($link);
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
