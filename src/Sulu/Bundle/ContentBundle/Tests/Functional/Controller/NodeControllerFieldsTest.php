<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Controller;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class NodeControllerFieldsTest extends SuluTestCase
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
        $this->initPhpcr();
    }

    public function testCGet()
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(3, $items);

        $this->assertNotNull($items[0]['id']);
        $this->assertEquals('/test-1', $items[0]['path']);
        $this->assertNotNull($items[1]['id']);
        $this->assertEquals('/test-2', $items[1]['path']);
        $this->assertNotNull($items[2]['id']);
        $this->assertEquals('/test-3', $items[2]['path']);
    }

    public function testCGetWithShadow()
    {
        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeShadow()
    {
        $this->createShadowPage('test-1', 'en', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-shadows' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhost()
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhost()
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-ghosts' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(2, $items);

        $this->assertEquals('test-2', $items[0]['title']);
        $this->assertEquals('test-3', $items[1]['title']);
    }

    public function testCGetWithGhostAndShadow()
    {
        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes', ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']);

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(3, $items);

        $this->assertEquals('test-1', $items[0]['title']);
        $this->assertEquals('test-2', $items[1]['title']);
        $this->assertEquals('test-3', $items[2]['title']);
    }

    public function testCGetExcludeGhostAndShadow()
    {
        $this->createPage('test-1', 'en');
        $this->createShadowPage('test-2', 'en', 'de');
        $this->createPage('test-3', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes',
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title', 'exclude-ghosts' => true, 'exclude-shadows' => true]
        );

        $result = json_decode($client->getResponse()->getContent(), true);

        $items = $result['_embedded']['nodes'];

        $this->assertCount(1, $items);

        $this->assertEquals('test-3', $items[0]['title']);
    }

    public function testGet()
    {
        $page1 = $this->createPage('test-1', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page1->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($page1->getUuid(), $result['id']);
        $this->assertTrue($result['hasChildren']);
        $this->assertEmpty($result['_embedded']['nodes']);
    }

    public function testGetTree()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de', [], $page1);
        $page4 = $this->createPage('test-4', 'de', [], $page1);
        $page5 = $this->createPage('test-5', 'de', [], $page2);
        $page6 = $this->createPage('test-6', 'de', [], $page2);
        $page7 = $this->createPage('test-7', 'de', [], $page3);
        $page8 = $this->createPage('test-8', 'de', [], $page4);
        $page9 = $this->createPage('test-9', 'de', [], $page6);
        $page10 = $this->createPage('test-10', 'de', [], $page6);
        $page11 = $this->createPage('test-11', 'de', [], $page10);
        $page12 = $this->createPage('test-12', 'de', [], $page10);
        $page13 = $this->createPage('test-13', 'de', [], $page12);
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page10->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'de', 'tree' => true, 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $layer = $result['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]['id']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(0, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page2->getUuid(), $layer[1]['id']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(2, $layer[1]['_embedded']['nodes']);

        $layer = $layer[1]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page5->getUuid(), $layer[0]['id']);
        $this->assertFalse($layer[0]['hasChildren']);
        $this->assertCount(0, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page6->getUuid(), $layer[1]['id']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(2, $layer[1]['_embedded']['nodes']);

        $layer = $layer[1]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page9->getUuid(), $layer[0]['id']);
        $this->assertFalse($layer[0]['hasChildren']);
        $this->assertCount(0, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page10->getUuid(), $layer[1]['id']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(2, $layer[1]['_embedded']['nodes']);

        $layer = $layer[1]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page11->getUuid(), $layer[0]['id']);
        $this->assertFalse($layer[0]['hasChildren']);
        $this->assertCount(0, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page12->getUuid(), $layer[1]['id']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(0, $layer[1]['_embedded']['nodes']);
    }

    public function testLinkedInternal()
    {
        $link = $this->createPage('test-1', 'en');
        $page = $this->createInternalLinkPage('test-2', 'en', $link);

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'en', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('internal', $result['linked']);
    }

    public function testLinkedExternal()
    {
        $page = $this->createExternalLinkPage('test-2', 'en', 'http://www.google.at');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'en', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('external', $result['linked']);
    }

    public function testTypeGhost()
    {
        $page = $this->createPage('test-2', 'en');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('ghost', $result['type']['name']);
        $this->assertEquals('en', $result['type']['value']);
    }

    public function testTypeShadow()
    {
        $page = $this->createShadowPage('test-2', 'en', 'de');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            sprintf('/api/nodes/%s', $page->getUuid()),
            ['webspace' => 'sulu_io', 'language' => 'de', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('shadow', $result['type']['name']);
        $this->assertEquals('en', $result['type']['value']);
    }

    public function testCGetActionSingleWebspaceNodes()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('my-test', 'de', [], null, [], '/cmf/test_io/contents/my-test');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes',
            ['webspace' => 'sulu_io', 'language' => 'de', 'webspace-nodes' => 'single', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $layer = $result['_embedded']['nodes'];
        $this->assertCount(1, $layer);
        $this->assertEquals($this->sessionManager->getContentNode('sulu_io')->getIdentifier(), $layer[0]['id']);
        $this->assertEquals('Sulu CMF', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(2, $layer[0]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertEmpty($layer[0]['_embedded']['nodes']);
        $this->assertEquals($page2->getUuid(), $layer[1]['id']);
        $this->assertEquals('test-2', $layer[1]['title']);
        $this->assertFalse($layer[1]['hasChildren']);
        $this->assertEmpty($layer[1]['_embedded']['nodes']);
    }

    public function testCGetActionAllWebspaceNodes()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('my-test', 'de', [], null, [], '/cmf/test_io/contents/my-test');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes',
            ['webspace' => 'sulu_io', 'language' => 'de', 'webspace-nodes' => 'all', 'fields' => 'title']
        );
        $result = json_decode($client->getResponse()->getContent(), true);
        $layer = $result['_embedded']['nodes'];

        usort($layer, function ($layer1, $layer2) {
            return strcmp($layer1['title'], $layer2['title']);
        });

        $this->assertCount(2, $layer);
        $this->assertEquals($this->sessionManager->getContentNode('sulu_io')->getIdentifier(), $layer[0]['id']);
        $this->assertEquals('Sulu CMF', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(2, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($this->sessionManager->getContentNode('test_io')->getIdentifier(), $layer[1]['id']);
        $this->assertEquals('Test CMF', $layer[1]['title']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(0, $layer[1]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertEmpty($layer[0]['_embedded']['nodes']);
        $this->assertEquals($page2->getUuid(), $layer[1]['id']);
        $this->assertEquals('test-2', $layer[1]['title']);
        $this->assertFalse($layer[1]['hasChildren']);
        $this->assertEmpty($layer[1]['_embedded']['nodes']);
    }

    public function testGetTreeActionSingleWebspaceNodes()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page11 = $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-1-1', 'de', [], $page11);
        $this->createPage('my-test', 'de', [], null, [], '/cmf/test_io/contents/my-test');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes/' . $page1->getUuid(),
            [
                'tree' => 'true',
                'webspace' => 'sulu_io',
                'language' => 'de',
                'webspace-nodes' => 'single',
                'fields' => 'title',
            ]
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $layer = $result['_embedded']['nodes'];
        usort($layer, function ($layer1, $layer2) {
            return strcmp($layer1['title'], $layer2['title']);
        });

        $this->assertCount(1, $layer);
        $this->assertEquals($this->sessionManager->getContentNode('sulu_io')->getIdentifier(), $layer[0]['id']);
        $this->assertEquals('Sulu CMF', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(2, $layer[0]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(1, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page2->getUuid(), $layer[1]['id']);
        $this->assertEquals('test-2', $layer[1]['title']);
        $this->assertFalse($layer[1]['hasChildren']);
        $this->assertEmpty($layer[1]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertEquals($page11->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertEmpty($layer[0]['_embedded']['nodes']);
    }

    public function testGetTreeActionAllWebspaceNodes()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page11 = $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-1-1', 'de', [], $page11);
        $this->createPage('my-test', 'de', [], null, [], '/cmf/test_io/contents/my-test');

        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/nodes/' . $page1->getUuid(),
            [
                'tree' => 'true',
                'webspace' => 'sulu_io',
                'language' => 'de',
                'webspace-nodes' => 'all',
                'fields' => 'title',
            ]
        );
        $result = json_decode($client->getResponse()->getContent(), true);

        $layer = $result['_embedded']['nodes'];
        usort($layer, function ($layer1, $layer2) {
            return strcmp($layer1['title'], $layer2['title']);
        });
        $this->assertCount(2, $layer);
        $this->assertEquals($this->sessionManager->getContentNode('sulu_io')->getIdentifier(), $layer[0]['id']);
        $this->assertEquals('Sulu CMF', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(2, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($this->sessionManager->getContentNode('test_io')->getIdentifier(), $layer[1]['id']);
        $this->assertEquals('Test CMF', $layer[1]['title']);
        $this->assertTrue($layer[1]['hasChildren']);
        $this->assertCount(0, $layer[1]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertCount(1, $layer[0]['_embedded']['nodes']);
        $this->assertEquals($page2->getUuid(), $layer[1]['id']);
        $this->assertEquals('test-2', $layer[1]['title']);
        $this->assertFalse($layer[1]['hasChildren']);
        $this->assertEmpty($layer[1]['_embedded']['nodes']);

        $layer = $layer[0]['_embedded']['nodes'];
        $this->assertEquals($page11->getUuid(), $layer[0]['id']);
        $this->assertEquals('test-1-1', $layer[0]['title']);
        $this->assertTrue($layer[0]['hasChildren']);
        $this->assertEmpty($layer[0]['_embedded']['nodes']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     * @param PageDocument $parent
     * @param array $permissions
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
        if ($parent !== null) {
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
