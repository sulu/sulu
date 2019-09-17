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

use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Sulu\Bundle\TestBundle\Testing\PHPCRImporter;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;

class PageControllerTest extends SuluTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var PHPCRImporter
     */
    private $importer;

    public function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthenticatedClient();
        $this->initPhpcr();
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->liveSession = $this->getContainer()->get('sulu_document_manager.live_session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->importer = new PHPCRImporter($this->session, $this->liveSession);
    }

    public function testGetFlatResponseWithoutFieldsAndParent()
    {
        $this->client->request('GET', '/api/pages?locale=en&flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $titles = array_map(function($page) {
            return $page->title;
        }, $response->_embedded->pages);

        $this->assertContains('Sulu CMF', $titles);
        $this->assertContains('Test CMF', $titles);
    }

    public function testGetFlatResponseForWebspace()
    {
        $this->client->request('GET', '/api/pages?locale=en&flat=true&webspace=sulu_io');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->pages);
        $this->assertEquals('Sulu CMF', $response->_embedded->pages[0]->title);
    }

    public function testGetFlatResponseWithParentAndWithoutWebspace()
    {
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $this->client->request('GET', '/api/pages?locale=en&flat=true&parentId=' . $webspaceUuid);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(0, $response->_embedded->pages);
    }

    public function testGetFlatResponseWithIds()
    {
        $webspaceUuids = [
            $this->session->getNode('/cmf/test_io/contents')->getIdentifier(),
            $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier(),
        ];

        $this->client->request('GET', '/api/pages?locale=en&flat=true&ids=' . implode(',', $webspaceUuids));

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $page1 = $response->_embedded->pages[0];
        $page2 = $response->_embedded->pages[1];
        $this->assertEquals('Homepage', $page1->title);
        $this->assertEquals('test_io', $page1->webspaceKey);
        $this->assertObjectHasAttribute('id', $page1);
        $this->assertObjectHasAttribute('uuid', $page1);
        $this->assertEquals('Homepage', $page2->title);
        $this->assertEquals('sulu_io', $page2->webspaceKey);
        $this->assertObjectHasAttribute('id', $page2);
        $this->assertObjectHasAttribute('uuid', $page2);
    }

    public function testGetWithPermissions()
    {
        $securedPage = $this->createPageDocument();
        $securedPage->setTitle('secured');
        $securedPage->setResourceSegment('/secured');
        $securedPage->setStructureType('default');
        $this->documentManager->persist($securedPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->client->request(
            'GET',
            '/api/pages?expandedIds=' . $securedPage->getUuid() . '&fields=title&webspace=sulu_io&language=en'
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response['_embedded']['pages'][0]);

        $this->client->request('GET', '/api/pages/' . $securedPage->getUuid() . '?language=en&webspace=sulu_io');

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response);
    }

    public function testSmallResponse()
    {
        $data = [
            [
                'template' => 'default',
                'title' => 'test1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test1',
                'article' => 'Test',
            ],
            [
                'template' => 'default',
                'title' => 'test2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test2',
                'article' => 'Test',
            ],
        ];

        $data = $this->setUpContent($data);

        $this->client->request('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&complete=false');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('path', $response);
        $this->assertArrayHasKey('nodeType', $response);
        $this->assertArrayHasKey('nodeState', $response);
        $this->assertArrayHasKey('internal', $response);
        $this->assertArrayHasKey('contentLocales', $response);
        $this->assertArrayHasKey('hasSub', $response);
        $this->assertArrayHasKey('order', $response);
        $this->assertArrayHasKey('linked', $response);
        $this->assertArrayHasKey('publishedState', $response);
        $this->assertArrayHasKey('published', $response);
        $this->assertArrayHasKey('navContexts', $response);
        $this->assertArrayNotHasKey('article', $response);
        $this->assertArrayNotHasKey('tags', $response);
        $this->assertArrayNotHasKey('ext', $response);
        $this->assertArrayHasKey('shadowLocales', $response);
        $this->assertArrayHasKey('contentLocales', $response);
        $this->assertArrayNotHasKey('shadowOn', $response);
        $this->assertArrayNotHasKey('shadowBaseLanguage', $response);
    }

    public function testPost()
    {
        $data1 = [
            'title' => 'news',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news',
            'article' => 'Test',
        ];
        $data2 = [
            'title' => 'test-1',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data1
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());
        $uuid = $response->id;

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $uuid . '&webspace=sulu_io&language=en',
            $data2
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('test-1', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/news/test', $response->url);
        $this->assertEquals(['tag1', 'tag2'], $response->tags);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->changer);

        /** @var NodeInterface $content */
        $defaultContent = $this->session->getNode('/cmf/sulu_io/contents/news/test-1');

        $this->assertEquals('test-1', $defaultContent->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $defaultContent->getProperty('i18n:en-article')->getString());
        $this->assertCount(2, $defaultContent->getPropertyValue('i18n:en-tags'));
        $this->assertEquals(WorkflowStage::TEST, $defaultContent->getPropertyValue('i18n:en-state'));
        $this->assertEquals($this->getTestUserId(), $defaultContent->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $defaultContent->getPropertyValue('i18n:en-changer'));
        $this->assertEquals($uuid, $defaultContent->getParent()->getIdentifier());

        /** @var NodeInterface $content */
        $liveContent = $this->liveSession->getNode('/cmf/sulu_io/contents/news/test-1');
        $this->assertEquals($liveContent->getIdentifier(), $defaultContent->getIdentifier());
        $this->assertFalse($liveContent->hasProperty('i18n:en-title'));
    }

    public function testPostAndPublish()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('Testtitle', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/test', $response->url);
        $this->assertEquals(['tag1', 'tag2'], $response->tags);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->changer);

        /** @var NodeInterface $content */
        $content = $this->session->getNode('/cmf/sulu_io/routes/en/test')->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertCount(2, $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals(WorkflowStage::PUBLISHED, $content->getPropertyValue('i18n:en-state'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-changer'));

        /** @var NodeInterface $content */
        $content = $this->liveSession->getNode('/cmf/sulu_io/routes/en/test')->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertCount(2, $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals(WorkflowStage::PUBLISHED, $content->getPropertyValue('i18n:en-state'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-changer'));
    }

    public function testPostWithExistingResourceLocator()
    {
        $data = [
            'title' => 'news',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testGet()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager')->clear();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=en');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals('/test-en', $response['path']);
        $this->assertEquals(['tag1', 'tag2'], $response['tags']);
        $this->assertEquals('/test_en', $response['url']);
        $this->assertEquals('Test English', $response['article']);

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_de', $response['title']);
        $this->assertEquals('/test-en', $response['path']);
        $this->assertEquals(['tag1', 'tag2'], $response['tags']);
        $this->assertEquals('/test_de', $response['url']);
        $this->assertEquals('Test German', $response['article']);
    }

    public function testGetAnotherTemplate()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        // change the template now to "simple"
        // the old data "article" should still exists
        $document->setStructureType('simple');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=en');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('article', $response);

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=en&template=default');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Test English', $response['article']);
    }

    public function testGetNotExisting()
    {
        $this->client->request('GET', '/api/pages/not-existing-id?language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetNotExistingTree()
    {
        $this->client->request('GET', '/api/pages?expandedIds=not-existing-id&webspace=sulu_io&language=en&fields=title,order,published');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetGhostContent()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);

        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de&ghost-content=true');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals(['name' => 'ghost', 'value' => 'en'], $response['type']);

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('', $response['title']);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testGetShadowContent()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $document->setShadowLocaleEnabled(true);
        $document->setShadowLocale('en');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals('Test English', $response['article']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);
        $this->assertEquals(['en', 'de'], $response['availableLocales']);
        $this->assertEquals(['en'], $response['contentLocales']);
        $this->assertEquals(['de' => 'en'], $response['shadowLocales']);
        $this->assertEquals(true, $response['shadowOn']);
    }

    public function testGetInternalLink()
    {
        $targetPage = $this->createPageDocument();
        $targetPage->setTitle('target');
        $targetPage->setResourceSegment('/target');
        $targetPage->setStructureType('default');
        $this->documentManager->persist($targetPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $internalLinkPage = $this->createPageDocument();
        $internalLinkPage->setTitle('page');
        $internalLinkPage->setStructureType('default');
        $internalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($internalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $internalLinkPage = $this->documentManager->find($internalLinkPage->getUuid());
        $internalLinkPage->setRedirectType(RedirectType::INTERNAL);
        $internalLinkPage->setRedirectTarget($targetPage);
        $this->documentManager->persist($internalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $internalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('internal', $response['linked']);
        $this->assertEquals($targetPage->getUuid(), $response['internal_link']);
    }

    public function testGetExternalLink()
    {
        $externalLinkPage = $this->createPageDocument();
        $externalLinkPage->setTitle('page');
        $externalLinkPage->setStructureType('default');
        $externalLinkPage->setRedirectType(RedirectType::EXTERNAL);
        $externalLinkPage->setRedirectExternal('http://www.sulu.io');
        $externalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($externalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $externalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('external', $response['linked']);
        $this->assertEquals('http://www.sulu.io', $response['external']);
    }

    public function testDelete()
    {
        $data = [
            [
                'template' => 'default',
                'title' => 'test1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test1',
                'article' => 'Test',
            ],
            [
                'template' => 'default',
                'title' => 'test2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test2',
                'article' => 'Test',
            ],
        ];

        $data = $this->setUpContent($data);

        $this->client->request('DELETE', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPut()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de',
            [
                'title' => 'Testtitle DE',
                'template' => 'default',
                'url' => '/test-de',
                'authored' => '2017-11-20T13:15:00',
                'author' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=en',
            [
                'title' => 'Testtitle EN',
                'template' => 'default',
                'url' => '/test-en',
                'authored' => '2017-11-20T13:15:00',
                'author' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Testtitle DE', $response['title']);
        $this->assertEquals('/test-de', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertEquals($this->getTestUserId(), $response['changer']);
        $this->assertEquals($this->getTestUserId(), $response['creator']);

        $this->assertEquals('2017-11-20T13:15:00', $response['authored']);
        $this->assertEquals(1, $response['author']);

        $this->client->request('GET', '/api/pages/' . $response['id'] . '?language=en');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Testtitle EN', $response['title']);
        $this->assertEquals('/test-en', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertEquals($this->getTestUserId(), $response['changer']);
        $this->assertEquals($this->getTestUserId(), $response['creator']);

        $this->assertEquals('2017-11-20T13:15:00', $response['authored']);
        $this->assertEquals(1, $response['author']);

        $this->assertFalse(
            $this->liveSession->getNode('/cmf/sulu_io/contents/testtitle-en')->hasProperty('i18n:en-changed')
        );
    }

    public function testPutAndPublish()
    {
        $data = [
            [
                'template' => 'default',
                'title' => 'test1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test1',
                'article' => 'Test',
            ],
        ];

        $data = $this->setUpContent($data);

        $data[0]['title'] = 'test123';
        $data[0]['tags'] = ['new tag'];
        $data[0]['article'] = 'thats a new article';

        $this->client->request(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=publish',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
        $this->assertEquals(true, $response->publishedState);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->creator);

        $this->client->request('GET', '/api/pages/' . $data[0]['id'] . '?language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
        $this->assertEquals(true, $response->publishedState);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->creator);

        /** @var NodeInterface $content */
        $content = $this->client->getContainer()->get('sulu_document_manager.live_session')
            ->getNode('/cmf/sulu_io/routes/en/test1')->getPropertyValue('sulu:content');

        $this->assertEquals('test123', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('thats a new article', $content->getProperty('i18n:en-article')->getString());
        $this->assertCount(1, $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-changer'));
    }

    public function testPutHomeWithChildren()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/test',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request('POST', '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $data = [
            'template' => 'default',
            'title' => 'Test',
        ];

        $this->client->request('GET', '/api/pages?webspace=sulu_io&language=en');
        $response = json_decode($this->client->getResponse()->getContent());
        $homepage = $response->_embedded->pages[0];

        $this->client->request(
            'PUT',
            '/api/pages/' . $homepage->id . '?webspace=sulu_io&language=en',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);

        $this->client->request('GET', '/api/pages/' . $homepage->id . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);
    }

    public function testPutNotExisting()
    {
        $this->client->request('PUT', '/api/pages/not-existing-id?language=de', []);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutWithTemplateChange()
    {
        $data = [
            [
                'template' => 'simple',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ];

        $data = $this->setUpContent($data);

        $data[0]['template'] = 'default';
        $data[0]['article'] = 'article test';

        $this->client->request(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);

        $this->client->request('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);
    }

    public function testPutShadow()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('PUT', '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de', $data);
        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de',
            array_merge($data, ['shadowOn' => true, 'shadowBaseLanguage' => 'en'])
        );
        $this->client->request('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('en', $response['shadowBaseLanguage']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);

        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de',
            array_merge($data, ['shadowOn' => false, 'shadowBaseLanguage' => null])
        );
        $this->client->request('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals(null, $response['shadowBaseLanguage']);
        $this->assertArrayNotHasKey('type', $response);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testPutRemoveShadowWithDifferentTemplate()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $document->setStructureType('overview');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $document->setShadowLocale('en');
        $document->setShadowLocaleEnabled(true);
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('default', $response['template']);

        $this->client->request(
            'PUT',
            '/api/pages/' . $document->getUuid() . '?language=de&webspace=sulu_io',
            [
                'id' => $document->getUuid(),
                'nodeType' => 1,
                'shadowOn' => false,
            ]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals('overview', $response['template']);
    }

    public function testPutWithValidHash()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request('POST', '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en', $data);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/api/pages/' . $response['id'] . '?language=en', $data);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=en&state=2',
            array_merge(['_hash' => $response['_hash']], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithInvalidHash()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $id = $response['id'];

        $this->client->request(
            'PUT',
            '/api/pages/' . $id . '?webspace=sulu_io&language=en&state=2',
            array_merge(['_hash' => md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1102, $response['code']);

        $this->client->request(
            'PUT',
            '/api/pages/' . $id . '?webspace=sulu_io&language=en&state=2&force=true',
            array_merge(['_hash' => md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithAlreadyExistingUrl()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $data['url'] = '/test2';

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $data['url'] = '/test';
        $this->client->request(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=en&state=2&action=publish',
            $data
        );
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1103, $response['code']);
    }

    public function testHistory()
    {
        $data = [
            'title' => 'news',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/a1',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $uuid = $response['id'];
        $data = [
            'title' => 'news',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/a2',
            'article' => 'Test',
        ];

        sleep(1);

        $this->client->request('PUT', '/api/pages/' . $uuid . '?webspace=sulu_io&language=en&action=publish', $data);
        $data = [
            'title' => 'news',
            'template' => 'default',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/a3',
            'article' => 'Test',
        ];
        $this->client->request('PUT', '/api/pages/' . $uuid . '?webspace=sulu_io&language=en&action=publish', $data);

        $this->client->request(
            'GET',
            '/api/pages/' . $uuid . '/resourcelocators?webspace=sulu_io&locale=en'
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/a2', $response['_embedded']['page_resourcelocators'][0]['path']);
        $this->assertEquals('/a1', $response['_embedded']['page_resourcelocators'][1]['path']);
    }

    public function testTreeGetTillId()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'GET',
            '/api/pages?expandedIds=' . $data[2]['id'] . '&fields=title&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $homepage = $response->_embedded->pages[0];

        // check if tree is correctly loaded till the given id
        $node1 = $homepage->_embedded->pages[0];
        $this->assertEquals($data[0]['path'], $node1->path);
        $this->assertFalse($node1->hasChildren);
        $this->assertEmpty($node1->_embedded->pages);

        $node2 = $homepage->_embedded->pages[1];
        $this->assertEquals($data[1]['path'], $node2->path);
        $this->assertTrue($node2->hasChildren);
        $this->assertCount(2, $node2->_embedded->pages);

        $node3 = $node2->_embedded->pages[0];
        $this->assertEquals($data[2]['path'], $node3->path);
        $this->assertFalse($node3->hasChildren);
        $this->assertCount(0, $node3->_embedded->pages);

        $node4 = $node2->_embedded->pages[1];
        $this->assertEquals($data[3]['path'], $node4->path);
        $this->assertTrue($node4->hasChildren);
        $this->assertNull($node4->_embedded->pages);
    }

    public function testTreeGetTillSelectedId()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'GET',
            '/api/pages?selectedIds=' . $data[2]['id'] . '&fields=title&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $homepage = $response->_embedded->pages[0];

        // check if tree is correctly loaded till the given id
        $node1 = $homepage->_embedded->pages[0];
        $this->assertEquals($data[0]['path'], $node1->path);
        $this->assertFalse($node1->hasChildren);
        $this->assertEmpty($node1->_embedded->pages);

        $node2 = $homepage->_embedded->pages[1];
        $this->assertEquals($data[1]['path'], $node2->path);
        $this->assertTrue($node2->hasChildren);
        $this->assertCount(2, $node2->_embedded->pages);

        $node3 = $node2->_embedded->pages[0];
        $this->assertEquals($data[2]['path'], $node3->path);
        $this->assertFalse($node3->hasChildren);
        $this->assertCount(0, $node3->_embedded->pages);

        $node4 = $node2->_embedded->pages[1];
        $this->assertEquals($data[3]['path'], $node4->path);
        $this->assertTrue($node4->hasChildren);
        $this->assertNull($node4->_embedded->pages);
    }

    public function testTreeGetTillSelectedIdWithoutWebspace()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'GET',
            '/api/pages?selectedIds=' . $data[2]['id'] . '&fields=title&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent());

        $homepage = $response->_embedded->pages[0];

        // check if tree is correctly loaded till the given id
        $node1 = $homepage->_embedded->pages[0];
        $this->assertEquals($data[0]['path'], $node1->path);
        $this->assertFalse($node1->hasChildren);
        $this->assertEmpty($node1->_embedded->pages);

        $node2 = $homepage->_embedded->pages[1];
        $this->assertEquals($data[1]['path'], $node2->path);
        $this->assertTrue($node2->hasChildren);
        $this->assertCount(2, $node2->_embedded->pages);

        $node3 = $node2->_embedded->pages[0];
        $this->assertEquals($data[2]['path'], $node3->path);
        $this->assertFalse($node3->hasChildren);
        $this->assertCount(0, $node3->_embedded->pages);

        $node4 = $node2->_embedded->pages[1];
        $this->assertEquals($data[3]['path'], $node4->path);
        $this->assertTrue($node4->hasChildren);
        $this->assertNull($node4->_embedded->pages);
    }

    public function testMove()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[4]['id'] . '?webspace=sulu_io&language=en&action=move&destination=' . $data[2]['id']
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[4]['id'], $response['id']);
        $this->assertEquals('test5', $response['title']);
        $this->assertEquals('/test2/test3/test5', $response['path']);
        $this->assertEquals('/test2/test3/testing5', $response['url']);

        $node = $this->session->getNode('/cmf/sulu_io/contents/test2/test3/test5');
        $this->assertEquals(
            $node->getPropertyValue('i18n:de-url', PropertyType::STRING),
            '/test2/test3/testing5'
        );
        $this->assertEquals(
            $node->getPropertyValue('i18n:en-url', PropertyType::STRING),
            '/test2/test3/testing5'
        );
        $this->assertEquals(
            $node->getPropertyValue('i18n:fr-url', PropertyType::STRING),
            '/test2/test3/testing5'
        );

        $englishRouteNode = $this->session->getNode('/cmf/sulu_io/routes/en/test2/test3/testing5');
        $this->assertEquals(
            $englishRouteNode->getPropertyValue('sulu:content', PropertyType::REFERENCE),
            $data[4]['id']
        );
        $germanRouteNode = $this->session->getNode('/cmf/sulu_io/routes/de/test2/test3/testing5');
        $this->assertEquals(
            $germanRouteNode->getPropertyValue('sulu:content', PropertyType::REFERENCE),
            $data[4]['id']
        );
        $frenchRouteNode = $this->session->getNode('/cmf/sulu_io/routes/de/test2/test3/testing5');
        $this->assertEquals(
            $frenchRouteNode->getPropertyValue('sulu:content', PropertyType::REFERENCE),
            $data[4]['id']
        );

        $rootNode = $this->session->getRootNode();
        $this->assertTrue($rootNode->hasNode('cmf/sulu_io/routes/de/test2/test3/testing5'));
        $this->assertTrue($rootNode->hasNode('cmf/sulu_io/routes/en/test2/test3/testing5'));
        $this->assertFalse($rootNode->hasNode('cmf/sulu_io/routes/de_at/test2/test3/testing5'));
        $this->assertFalse($rootNode->hasNode('cmf/sulu_io/routes/en_us/test2/test3/testing5'));
        $this->assertTrue($rootNode->hasNode('cmf/sulu_io/routes/fr/test2/test3/testing5'));
    }

    public function testMoveNonExistingSource()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/123-123?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testMoveNonExistingDestination()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=123-123'
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCopy()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[4]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=' . $data[2]['id']
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertNotEquals($data[4]['id'], $response['id']);
        $this->assertEquals('test5', $response['title']);
        $this->assertEquals('/test2/test3/test5', $response['path']);
        $this->assertEquals('/test2/test3/testing5', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertNull($response['published']);

        // check old node
        $this->client->request(
            'GET',
            '/api/pages/' . $data[4]['id'] . '?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($data[4]['id'], $response['id']);
        $this->assertEquals($data[4]['title'], $response['title']);
        $this->assertEquals($data[4]['path'], $response['path']);
        $this->assertEquals($data[4]['template'], $response['template']);
        $this->assertEquals($data[4]['url'], $response['url']);
        $this->assertEquals($data[4]['article'], $response['article']);

        $rootNode = $this->session->getRootNode();
        $this->assertFalse($rootNode->hasNode('cmf/sulu_io/routes/de/test2/test3/testing5'));
        $this->assertFalse($rootNode->hasNode('cmf/sulu_io/routes/en/test2/test3/testing5'));
    }

    public function testCopyWithShadow()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_en');
        $document->setResourceSegment('/test_en');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();

        $document = $this->documentManager->find($document->getUuid(), 'de', ['load_ghost_content' => false]);
        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $document->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $document->setShadowLocaleEnabled(true);
        $document->setShadowLocale('en');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            sprintf(
                '/api/pages/%s?webspace=sulu_io&language=en&action=copy&destination=%s',
                $document->getUuid(),
                $document->getUuid()
            )
        );

        $uuid = json_decode($this->client->getResponse()->getContent(), true)['id'];

        $germanDocument = $this->documentManager->find($uuid, 'de');
        $this->assertStringStartsWith('/test_de/test_de', $germanDocument->getResourceSegment());

        $englishDocument = $this->documentManager->find($uuid, 'en');
        $this->assertStringStartsWith('/test_en/test_en', $englishDocument->getResourceSegment());
    }

    public function testCopyNonExistingSource()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/123-123?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCopyNonExistingDestination()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=123-123'
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testUnpublish()
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $this->client->request(
            'POST',
            '/api/pages/' . $document->getUuid() . '?action=unpublish&language=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertFalse($defaultNode->hasProperty('i18n:de-published'));
        $this->assertEquals(WorkflowStage::TEST, $defaultNode->getPropertyValue('i18n:de-state'));

        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEmpty($liveNode->getProperties('i18n:de-*'));
    }

    public function testRemoveDraft()
    {
        $document = $this->createPageDocument();
        $document->setTitle('published title');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $document = $this->documentManager->find($document->getUuid(), 'de');
        $document->setTitle('draft title');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('draft title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));

        $this->client->request(
            'POST',
            '/api/pages/' . $document->getUuid() . '?action=remove-draft&webspace=sulu_io&language=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('published title', $response['title']);

        $this->session->refresh(false);
        $this->liveSession->refresh(false);

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));
    }

    public function testRemoveDraftWithoutWebspace()
    {
        $document = $this->createPageDocument();
        $document->setTitle('published title');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $document = $this->documentManager->find($document->getUuid(), 'de');
        $document->setTitle('draft title');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('draft title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));

        $this->client->request(
            'POST',
            '/api/pages/' . $document->getUuid() . '?action=remove-draft&language=de'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testOrder()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/order.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[1]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals('/test2', $response['path']);
        $this->assertEquals('/test2', $response['url']);
        $this->assertEquals(30, $response['order']);

        $this->client->request(
            'POST',
            '/api/pages/' . $data[3]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals('/test4', $response['path']);
        $this->assertEquals('/test4', $response['url']);
        $this->assertEquals(10, $response['order']);

        $this->client->request('GET', '/api/pages?fields=title,order&webspace=sulu_io&language=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $homepage = $response['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertEquals(4, count($items));
        $this->assertEquals('test4', $items[0]['title']);
        $this->assertEquals(10, $items[0]['order']);
        $this->assertEquals('test1', $items[1]['title']);
        $this->assertEquals(20, $items[1]['order']);
        $this->assertEquals('test3', $items[2]['title']);
        $this->assertEquals(30, $items[2]['order']);
        $this->assertEquals('test2', $items[3]['title']);
        $this->assertEquals(40, $items[3]['order']);
    }

    public function testOrderWithGhosts()
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/order.xml');

        $this->client->request(
            'POST',
            '/api/pages/' . $data[1]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals(30, $response['order']);

        $this->client->request(
            'POST',
            '/api/pages/' . $data[3]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals(10, $response['order']);

        $this->client->request('GET', '/api/pages?fields=title,order&webspace=sulu_io&language=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $homepage = $response['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertEquals(4, count($items));
        $this->assertEquals('test4', $items[0]['title']);
        $this->assertEquals(10, $items[0]['order']);
        $this->assertEquals('test1', $items[1]['title']);
        $this->assertEquals(20, $items[1]['order']);
        $this->assertEquals('test3', $items[2]['title']);
        $this->assertEquals(30, $items[2]['order']);
        $this->assertEquals('test2', $items[3]['title']);
        $this->assertEquals(40, $items[3]['order']);
    }

    public function testOrderNonExistingSource()
    {
        $this->client->request(
            'POST',
            '/api/pages/123-123-123?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testOrderNonExistingPosition()
    {
        $data = [
            [
                'title' => 'test1',
                'template' => 'default',
                'url' => '/test1',
            ],
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data[0]
        );
        $data[0] = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 42,
            ]
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testNavContexts()
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'tags' => [
                'tag1',
            ],
            'url' => '/test1',
            'article' => 'Test',
            'navContexts' => ['main', 'footer'],
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('test1', $data['title']);
        $this->assertEquals('/test1', $data['path']);
        $this->assertEquals(1, $data['nodeState']);
        $this->assertFalse($data['publishedState']);
        $this->assertEquals(['main', 'footer'], $data['navContexts']);
        $this->assertFalse($data['hasSub']);
        $this->assertArrayHasKey('_links', $data);

        $this->client->request('GET', '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test1', $response['path']);
        $this->assertFalse($response['publishedState']);
        $this->assertEquals(['main', 'footer'], $response['navContexts']);
        $this->assertFalse($response['hasSub']);
    }

    public function testPostTriggerAction()
    {
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $this->client->request(
            'POST',
            '/api/pages/' . $webspaceUuid . '?webspace=sulu_io&action=copy-locale&locale=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testCopyLocale()
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertEquals($data['article'], $result['article']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);
    }

    public function testCopyMultipleLocales()
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de,de_at'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->request(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);

        $this->client->request(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de_at'
        );
        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);
    }

    public function testInternalLinkAutoName()
    {
        $data = [
            [
                'template' => 'internallinks',
                'title' => 'test1',
                'url' => '/test1',
                'internalLinks' => [],
            ],
            [
                'template' => 'default',
                'title' => 'test2',
                'url' => '/test1/test2',
            ],
        ];

        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data[0]
        );
        $data[0] = json_decode($this->client->getResponse()->getContent(), true);
        $this->client->request('POST', '/api/pages?webspace=sulu_io&language=en&parentId=' . $data[0]['id'], $data[1]);
        $data[1] = json_decode($this->client->getResponse()->getContent(), true);

        $data[0]['internalLinks'][] = $data[1]['id'];
        $this->client->request('PUT', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($this->client->getResponse()->getContent(), true);

        $data[0]['title'] = 'Dornbirn';
        $this->client->request('PUT', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/dornbirn', $result['path']);
        $this->assertEquals('Dornbirn', $result['title']);
    }

    public function testRenamePageWithLinkedChild()
    {
        $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $document = $this->documentManager->find('585ccd35-a98e-4e41-a62c-e502ca905496', 'en');
        $document->setStructureType('internallinks');
        $document->getStructure()->bind(
            [
                'internalLinks' => [
                    '5778b19f-460a-47fc-93da-9a6126e5c384',
                ],
            ]
        );
        $this->documentManager->persist($document, 'en');
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->client->request('GET', '/api/pages/' . $document->getUuid() . '?webspace=sulu_io&language=en');
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $data['title'] = 'Sulu is awesome';

        $this->client->request(
            'PUT',
            '/api/pages/' . $document->getUuid() . '?webspace=sulu_io&language=en&action=publish',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/sulu-is-awesome', $data['path']);

        /** @var SessionInterface $liveSession */
        $liveSession = $this->getContainer()->get('sulu_document_manager.live_session');
        /** @var SessionInterface $session */
        $session = $this->getContainer()->get('sulu_document_manager.default_session');

        $node = $liveSession->getNode('/cmf/sulu_io/contents/sulu-is-awesome');
        $this->assertEquals($data['id'], $node->getIdentifier());

        $node = $session->getNode('/cmf/sulu_io/contents/sulu-is-awesome');
        $this->assertEquals($data['id'], $node->getIdentifier());
    }

    public function testDeleteReferencedNode()
    {
        $linkedDocument = $this->createPageDocument();
        $linkedDocument->setTitle('test1');
        $linkedDocument->setResourceSegment('/test1');
        $linkedDocument->setStructureType('simple');
        $this->documentManager->persist($linkedDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($linkedDocument, 'en');
        $this->documentManager->flush();

        $document = $this->createPageDocument();
        $document->setTitle('test2');
        $document->setResourceSegment('/test2');
        $document->setStructureType('internallinks');
        $document->getStructure()->bind([
            'internalLinks' => [
                $linkedDocument->getUuid(),
            ],
        ]);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();

        $this->client->request('DELETE', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['items'][0]);
        $this->assertEquals('test2', $response['items'][0]['name']);

        $this->client->request('DELETE', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en&force=true');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->request('GET', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * @return PageDocument
     */
    private function createPageDocument()
    {
        return $this->documentManager->create('page');
    }

    private function setUpContent($data)
    {
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        for ($i = 0; $i < count($data); ++$i) {
            $this->client->request(
                'POST',
                '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
                $data[$i]
            );
            $data[$i] = (array) json_decode($this->client->getResponse()->getContent(), true);
        }

        return $data;
    }
}
