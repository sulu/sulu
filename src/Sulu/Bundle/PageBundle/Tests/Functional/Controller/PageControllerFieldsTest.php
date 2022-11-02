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

use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PageControllerFieldsTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->initPhpcr();
    }

    public function testCGet(): void
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest('GET', '/api/pages', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(3, $items);

        $this->assertNotNull($items[0]['id']);
        $this->assertEquals('/test-1', $items[0]['path']);
        $this->assertNotNull($items[1]['id']);
        $this->assertEquals('/test-2', $items[1]['path']);
        $this->assertNotNull($items[2]['id']);
        $this->assertEquals('/test-3', $items[2]['path']);
    }

    public function testCGetWithShadow(): void
    {
        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest('GET', '/api/pages', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeShadow(): void
    {
        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest(
            'GET',
            '/api/pages',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-shadows' => 'true']
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhost(): void
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest('GET', '/api/pages', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhost(): void
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest(
            'GET',
            '/api/pages',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-ghosts' => 'true']
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhostAndShadow(): void
    {
        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest('GET', '/api/pages', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhostAndShadow(): void
    {
        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $this->client->jsonRequest(
            'GET',
            '/api/pages',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-ghosts' => 'true', 'exclude-shadows' => 'true']
        );

        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $homepage = $result['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertCount(1, $items);

        $this->assertEquals('test-3', $items[0]['title']);
    }

    public function testLinkedInternal(): void
    {
        $link = $this->createPage('test-1', 'en');
        $page = $this->createInternalLinkPage('test-2', 'en', $link);

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/pages/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'en', 'fields' => 'title']
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('internal', $result['linked']);
    }

    public function testLinkedExternal(): void
    {
        $page = $this->createExternalLinkPage('test-2', 'en', 'http://www.google.at');

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/pages/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'en', 'fields' => 'title']
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('external', $result['linked']);
    }

    public function testTypeShadow(): void
    {
        $page = $this->createShadowPage('test-2', 'en', 'de');

        $this->client->jsonRequest(
            'GET',
            \sprintf('/api/pages/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('shadow', $result['type']['name']);
        $this->assertEquals('en', $result['type']['value']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     * @param PageDocument $parent
     * @param string $path
     *
     * @return PageDocument
     */
    private function createPage($title, $locale, $data = [], $parent = null, array $permissions = [], $path = null)
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');

        if (!$path) {
            $path = $this->sessionManager->getContentPath('sulu_io') . '/' . $title;
        }
        if (null !== $parent) {
            $path = $parent->getPath() . '/' . $title;
            $document->setParent($parent);
        }

        $data['title'] = $title;
        $data['url'] = '/' . $title;

        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::NONE);
        $document->setShadowLocaleEnabled(false);
        $document->getStructure()->bind($data);
        $document->setPermissions($permissions);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'path' => $path,
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
        $document->setTitle(\strrev($title));
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

    /**
     * @param string $title
     * @param string $locale
     * @param string $link
     *
     * @return PageDocument
     */
    private function createExternalLinkPage($title, $locale, $link)
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::EXTERNAL);
        $document->setRedirectExternal($link);
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
