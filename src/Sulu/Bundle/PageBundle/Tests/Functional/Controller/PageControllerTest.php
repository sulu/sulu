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

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\SessionInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\PHPCRImporter;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PageControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var PHPCRImporter
     */
    private $importer;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->client->disableReboot(); // see https://github.com/symfony/symfony/issues/45580
        $this->purgeDatabase();
        $this->initPhpcr();
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->liveSession = $this->getContainer()->get('sulu_document_manager.live_session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->accessControlManager = $this->getContainer()->get('sulu_security.access_control_manager');
        $this->importer = new PHPCRImporter($this->session, $this->liveSession);
    }

    public function testGetFlatResponseWithoutFieldsAndParent(): void
    {
        $this->client->jsonRequest('GET', '/api/pages?locale=en&flat=true');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $titles = \array_map(function($page) {
            return $page->title;
        }, $response->_embedded->pages);

        $this->assertContains('Sulu CMF', $titles);
        $this->assertContains('Test CMF', $titles);
    }

    public function testGetFlatResponseForWebspace(): void
    {
        $this->client->jsonRequest('GET', '/api/pages?locale=en&flat=true&webspace=sulu_io');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $response->_embedded->pages);
        $this->assertEquals('Sulu CMF', $response->_embedded->pages[0]->title);
    }

    public function testGetFlatResponseWithParentAndWithoutWebspace(): void
    {
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $this->client->jsonRequest('GET', '/api/pages?locale=en&flat=true&parentId=' . $webspaceUuid);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(0, $response->_embedded->pages);
    }

    public function testGetFlatResponseWithIds(): void
    {
        $webspaceUuids = [
            $this->session->getNode('/cmf/test_io/contents')->getIdentifier(),
            $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier(),
        ];

        $this->client->jsonRequest('GET', '/api/pages?locale=en&flat=true&ids=' . \implode(',', $webspaceUuids));

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $page1 = $response->_embedded->pages[0];
        $page2 = $response->_embedded->pages[1];
        $this->assertEquals('Homepage', $page1->title);
        $this->assertEquals('test_io', $page1->webspaceKey);
        $this->assertObjectHasAttribute('id', $page1);
        $this->assertEquals('Homepage', $page2->title);
        $this->assertEquals('sulu_io', $page2->webspaceKey);
        $this->assertObjectHasAttribute('id', $page2);
    }

    public function testGetFlatResponseWithGhostIds(): void
    {
        $ghostDocument = $this->createPageDocument();
        $ghostDocument->setTitle('ghost_test_en');
        $ghostDocument->setResourceSegment('/test_en');
        $ghostDocument->setStructureType('default');
        $ghostDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);

        $this->documentManager->persist($ghostDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);

        $shadowDocument = $this->createPageDocument();
        $shadowDocument->setTitle('shadow_test_en');
        $shadowDocument->setResourceSegment('/test_en');
        $shadowDocument->setStructureType('default');
        $shadowDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($shadowDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $shadowDocument->setTitle('shadow_test_de');
        $shadowDocument->setResourceSegment('/test_de');
        $shadowDocument->setStructureType('default');
        $shadowDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $shadowDocument->setShadowLocaleEnabled(true);
        $shadowDocument->setShadowLocale('en');
        $this->documentManager->persist($shadowDocument, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/pages?locale=de&flat=true&ids=' . $ghostDocument->getUuid() . ',' . $shadowDocument->getUuid()
        );

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $response->_embedded->pages);

        $page1 = $response->_embedded->pages[0];
        $page2 = $response->_embedded->pages[1];
        $this->assertEquals('ghost_test_en', $page1->title);
        $this->assertEquals('de', $page1->locale);
        $this->assertEquals('en', $page1->ghostLocale);
        $this->assertEquals('sulu_io', $page1->webspaceKey);
        $this->assertEquals('shadow_test_en', $page2->title);
        $this->assertEquals('de', $page2->locale);
        $this->assertEquals('en', $page2->shadowLocale);
        $this->assertEquals('sulu_io', $page2->webspaceKey);
    }

    public function testGetFlatResponseWithShadowAndGhostContent(): void
    {
        $ghostDocument = $this->createPageDocument();
        $ghostDocument->setTitle('ghost_test_en');
        $ghostDocument->setResourceSegment('/test_en');
        $ghostDocument->setStructureType('default');
        $ghostDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);

        $this->documentManager->persist($ghostDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $shadowDocument = $this->createPageDocument();
        $shadowDocument->setTitle('shadow_test_en');
        $shadowDocument->setResourceSegment('/test_en');
        $shadowDocument->setStructureType('default');
        $shadowDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test English',
        ]);
        $this->documentManager->persist($shadowDocument, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $shadowDocument->setTitle('shadow_test_de');
        $shadowDocument->setResourceSegment('/test_de');
        $shadowDocument->setStructureType('default');
        $shadowDocument->getStructure()->bind([
            'tags' => [
                'tag1',
                'tag2',
            ],
            'article' => 'Test German',
        ]);
        $shadowDocument->setShadowLocaleEnabled(true);
        $shadowDocument->setShadowLocale('en');
        $this->documentManager->persist($shadowDocument, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->jsonRequest('GET', '/api/pages?locale=de&flat=true&webspace=sulu_io');
        $response = \json_decode($this->client->getResponse()->getContent());

        $childPages = $response->_embedded->pages[0]->_embedded->pages;

        $this->assertEquals('ghost_test_en', $childPages[0]->title);
        $this->assertEquals('de', $childPages[0]->locale);
        $this->assertEquals('en', $childPages[0]->ghostLocale);
        $this->assertEquals('sulu_io', $childPages[0]->webspaceKey);
        $this->assertObjectNotHasAttribute('shadowLocale', $childPages[0]);
        $this->assertEquals('ghost', $childPages[0]->type->name);
        $this->assertEquals('en', $childPages[0]->type->value);

        $this->assertEquals('shadow_test_en', $childPages[1]->title);
        $this->assertEquals('de', $childPages[1]->locale);
        $this->assertEquals('en', $childPages[1]->shadowLocale);
        $this->assertEquals('sulu_io', $childPages[1]->webspaceKey);
        $this->assertObjectNotHasAttribute('ghostLocale', $childPages[1]);
        $this->assertEquals('shadow', $childPages[1]->type->name);
        $this->assertEquals('en', $childPages[1]->type->value);
    }

    public function testGetWithPermissions(): void
    {
        $securedPage = $this->createPageDocument();
        $securedPage->setTitle('secured');
        $securedPage->setResourceSegment('/secured');
        $securedPage->setStructureType('default');
        $this->documentManager->persist($securedPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->client->jsonRequest(
            'GET',
            '/api/pages?expandedIds=' . $securedPage->getUuid() . '&fields=title&webspace=sulu_io&language=en'
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response['_embedded']['pages'][0]);

        $this->client->jsonRequest('GET', '/api/pages/' . $securedPage->getUuid() . '?language=en&webspace=sulu_io');

        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response);
    }

    public function testSmallResponse(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&complete=false');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

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

    public function testPost(): void
    {
        $role = $this->createRole();
        $this->em->flush();

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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $permissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => false,
                'archive' => false,
                'live' => false,
                'security' => false,
            ],
        ];

        $homeDocument->setPermissions($permissions);
        $this->documentManager->persist($homeDocument, 'en');
        $this->documentManager->flush();

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data1
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());
        $uuid = $response->id;

        $this->assertEquals(
            $permissions,
            $this->accessControlManager->getPermissions(SecurityBehavior::class, $response->id)
        );

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $uuid . '&webspace=sulu_io&language=en',
            $data2
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testPostAndPublish(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testPostWithExistingResourceLocator(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(409, $this->client->getResponse());
    }

    public function testPostWithBlockSettings(): void
    {
        $data = [
            'title' => 'Block',
            'template' => 'block',
            'url' => '/block',
            'article' => [
                [
                    'type' => 'test',
                    'title' => 'Block 1',
                    'article' => 'Block 1 Article',
                    'settings' => ['segments' => ['sulu_io' => 's']],
                ],
                [
                    'type' => 'test',
                    'title' => 'Block 2',
                    'article' => 'Block 2 Article',
                    'settings' => ['segments' => ['sulu_io' => 'w']],
                ],
            ],
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->client->request(
            'GET',
            '/api/pages/' . $response->id . '?webspace=sulu_io&language=en'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $response = \json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('s', $response->article[0]->settings->segments->sulu_io);
        $this->assertEquals('w', $response->article[1]->settings->segments->sulu_io);
    }

    public function testGet(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals('sulu_io', $response['webspace']);
        $this->assertEquals('/test-en', $response['path']);
        $this->assertEquals(['tag1', 'tag2'], $response['tags']);
        $this->assertEquals('/test_en', $response['url']);
        $this->assertEquals('Test English', $response['article']);

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_de', $response['title']);
        $this->assertEquals('sulu_io', $response['webspace']);
        $this->assertEquals('/test-en', $response['path']);
        $this->assertEquals(['tag1', 'tag2'], $response['tags']);
        $this->assertEquals('/test_de', $response['url']);
        $this->assertEquals('Test German', $response['article']);
    }

    public function testGetAnotherTemplate(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('article', $response);

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=en&template=default');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Test English', $response['article']);
    }

    public function testGetNotExisting(): void
    {
        $this->client->jsonRequest('GET', '/api/pages/not-existing-id?language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetNotExistingTree(): void
    {
        $this->client->jsonRequest('GET', '/api/pages?expandedIds=not-existing-id&webspace=sulu_io&language=en&fields=title,order,published');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testGetGhostContent(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de&ghost-content=true');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals(['name' => 'ghost', 'value' => 'en'], $response['type']);
        $this->assertEquals('en', $response['ghostLocale']);

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('', $response['title']);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testGetShadowContent(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('test_en', $response['title']);
        $this->assertEquals('Test English', $response['article']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);
        $this->assertEquals('en', $response['shadowLocale']);
        $this->assertEquals(['en', 'de'], $response['availableLocales']);
        $this->assertEquals(['en'], $response['contentLocales']);
        $this->assertEquals(['de' => 'en'], $response['shadowLocales']);
        $this->assertEquals(true, $response['shadowOn']);
    }

    public function testGetInternalLink(): void
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

        /** @var BasePageDocument $internalLinkPage */
        $internalLinkPage = $this->documentManager->find($internalLinkPage->getUuid());
        $internalLinkPage->setRedirectType(RedirectType::INTERNAL);
        $internalLinkPage->setRedirectTarget($targetPage);
        $this->documentManager->persist($internalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->jsonRequest('GET', '/api/pages/' . $internalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('internal', $response['linked']);
        $this->assertEquals($targetPage->getUuid(), $response['internal_link']);
    }

    public function testGetExternalLink(): void
    {
        $externalLinkPage = $this->createPageDocument();
        $externalLinkPage->setTitle('page');
        $externalLinkPage->setStructureType('default');
        $externalLinkPage->setRedirectType(RedirectType::EXTERNAL);
        $externalLinkPage->setRedirectExternal('http://www.sulu.io');
        $externalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($externalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->jsonRequest('GET', '/api/pages/' . $externalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('external', $response['linked']);
        $this->assertEquals('http://www.sulu.io', $response['external']);
    }

    public function testDelete(): void
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

        static::assertCount(0, $this->em->getRepository(TrashItemInterface::class)->findAll());

        $this->client->jsonRequest('DELETE', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        /** @var TrashItemInterface[] $trashItems */
        $trashItems = $this->em->getRepository(TrashItemInterface::class)->findAll();
        static::assertCount(1, $trashItems);
        static::assertSame(BasePageDocument::RESOURCE_KEY, $trashItems[0]->getResourceKey());

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testDeleteWithChildren(): void
    {
        $page1 = $this->setUpContent([
            [
                'template' => 'default',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ])[0];

        $page1_1 = $this->setUpContent([
            [
                'parentUuid' => $page1['id'],
                'template' => 'default',
                'title' => 'test1-1',
                'url' => '/test1-1',
            ],
        ])[0];

        $page1_2 = $this->setUpContent([
            [
                'parentUuid' => $page1['id'],
                'template' => 'default',
                'title' => 'test1-2',
                'url' => '/test1-2',
            ],
        ])[0];

        $page1_2_1 = $this->setUpContent([
            [
                'parentUuid' => $page1_2['id'],
                'template' => 'default',
                'title' => 'test1-2-1',
                'url' => '/test1-2-1',
            ],
        ])[0];

        $this->client->jsonRequest('DELETE', '/api/pages/' . $page1['id'] . '?webspace=sulu_io&language=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(409, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1105,
            'message' => 'Resource has 3 dependant resources.',
            'resource' => [
                'id' => $page1['id'],
                'resourceKey' => 'pages',
            ],
            'dependantResourcesCount' => 3,
            'dependantResourceBatches' => [
                [
                    [
                        'id' => $page1_2_1['id'],
                        'resourceKey' => 'pages',
                    ],
                ],
                [
                    [
                        'id' => $page1_1['id'],
                        'resourceKey' => 'pages',
                    ],
                    [
                        'id' => $page1_2['id'],
                        'resourceKey' => 'pages',
                    ],
                ],
            ],
            'title' => 'Delete 3 subpages?',
            'detail' => 'Are you sure that you also want to delete 3 subpages?',
        ], $content);
    }

    public function testDeleteWithChildrenWithoutPermissions(): void
    {
        $role = $this->createRole('User', 'Sulu');

        $userRole = new UserRole();
        $userRole->setUser($this->getTestUser());
        $userRole->setLocale('["en-gb", "de"]');
        $userRole->setRole($role);
        $this->em->persist($userRole);

        $this->getTestUser()->addUserRole($userRole);
        $this->em->flush();

        $permissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => false,
                'archive' => true,
                'live' => true,
                'security' => true,
            ],
        ];

        $fullPermissions = [
            $role->getId() => [
                'view' => true,
                'edit' => true,
                'add' => true,
                'delete' => true,
                'archive' => true,
                'live' => true,
                'security' => true,
            ],
        ];

        $page1 = $this->setUpContent([
            [
                'template' => 'default',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ])[0];

        $page1_1 = $this->setUpContent([
            [
                'parentUuid' => $page1['id'],
                'template' => 'default',
                'title' => 'test1-1',
                'url' => '/test1-1',
            ],
        ])[0];

        $page1_2 = $this->setUpContent([
            [
                'parentUuid' => $page1['id'],
                'template' => 'default',
                'title' => 'test1-2',
                'url' => '/test1-2',
            ],
        ])[0];

        $page1_2_1 = $this->setUpContent([
            [
                'parentUuid' => $page1_2['id'],
                'template' => 'default',
                'title' => 'test1-2-1',
                'url' => '/test1-2-1',
            ],
        ])[0];

        $this->accessControlManager->setPermissions(SecurityBehavior::class, (string) $page1_1['id'], $permissions);
        $this->accessControlManager->setPermissions(SecurityBehavior::class, (string) $page1_2['id'], $fullPermissions);
        $this->accessControlManager->setPermissions(SecurityBehavior::class, (string) $page1_2_1['id'], $permissions);

        $this->client->jsonRequest('DELETE', '/api/pages/' . $page1['id'] . '?webspace=sulu_io&language=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(403, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1104,
            'message' => 'Insufficient permissions for 2 descendant elements.',
            'detail' => 'Insufficient permissions for 2 descendant elements.',
        ], $content);
    }

    public function testDeleteLocale(): void
    {
        $data = [
            [
                'template' => 'default',
                'title' => 'test1',
                'description' => 'test1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test1',
                'article' => 'Test',
            ],
        ];

        $data = $this->setUpContent($data);

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=de',
            [
                'title' => 'Testtitle DE',
                'template' => 'default',
                'url' => '/test-de',
                'authored' => '2017-11-20T13:15:00',
                'author' => 1,
            ]
        );

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertCount(2, \json_decode($this->client->getResponse()->getContent(), true)['contentLocales']);

        $this->client->jsonRequest('DELETE', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&deleteLocale=true');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertCount(1, \json_decode($this->client->getResponse()->getContent(), true)['contentLocales']);
    }

    public function testPut(): void
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
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
        $this->client->jsonRequest(
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

        $this->client->jsonRequest('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Testtitle DE', $response['title']);
        $this->assertEquals('/test-de', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertEquals($this->getTestUserId(), $response['changer']);
        $this->assertEquals($this->getTestUserId(), $response['creator']);

        $this->assertEquals('2017-11-20T13:15:00', $response['authored']);
        $this->assertEquals(1, $response['author']);

        $this->client->jsonRequest('GET', '/api/pages/' . $response['id'] . '?language=en');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

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

    public function testPutAndPublish(): void
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

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=publish',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
        $this->assertEquals(true, $response->publishedState);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->creator);

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testPutHomeWithChildren(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest('POST', '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $data = [
            'template' => 'default',
            'title' => 'Test',
        ];

        $this->client->jsonRequest('GET', '/api/pages?webspace=sulu_io&language=en');
        $response = \json_decode($this->client->getResponse()->getContent());
        $homepage = $response->_embedded->pages[0];

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $homepage->id . '?webspace=sulu_io&language=en',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);

        $this->client->jsonRequest('GET', '/api/pages/' . $homepage->id . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);
    }

    public function testPutNotExisting(): void
    {
        $this->client->jsonRequest('PUT', '/api/pages/not-existing-id?language=de', []);
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testPutWithTemplateChange(): void
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

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);

        $this->client->jsonRequest('GET', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);
    }

    public function testPutWithMissingNodeInLiveWorkspace(): void
    {
        $data = [
            [
                'template' => 'simple',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ];
        $data = $this->setUpContent($data);

        // simulate error during page creation be removing node from live workspace
        $this->liveSession->getNodeByIdentifier($data[0]['id'])->remove();
        $this->liveSession->save();

        $data[0]['title'] = 'new title';

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent() ?: '');
        $this->assertEquals('new title', $response->title);
    }

    public function testPutShadow(): void
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('PUT', '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de', $data);
        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de',
            \array_merge($data, ['shadowOn' => true, 'shadowBaseLanguage' => 'en'])
        );
        $this->client->jsonRequest('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('en', $response['shadowBaseLanguage']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=de',
            \array_merge($data, ['shadowOn' => false, 'shadowBaseLanguage' => null])
        );
        $this->client->jsonRequest('GET', '/api/pages/' . $response['id'] . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals(null, $response['shadowBaseLanguage']);
        $this->assertArrayNotHasKey('type', $response);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testPutRemoveShadowWithDifferentTemplate(): void
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('overview', $response['template']);

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $document->getUuid() . '?language=de&webspace=sulu_io',
            [
                'id' => $document->getUuid(),
                'nodeType' => 1,
                'shadowOn' => false,
            ]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?language=de');
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals('overview', $response['template']);
    }

    public function testPutWithValidHash(): void
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest('POST', '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en', $data);
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('GET', '/api/pages/' . $response['id'] . '?language=en', $data);
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=en&state=2',
            \array_merge(['_hash' => $response['_hash']], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithInvalidHash(): void
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $id = $response['id'];

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $id . '?webspace=sulu_io&language=en&state=2',
            \array_merge(['_hash' => \md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1102, $response['code']);

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $id . '?webspace=sulu_io&language=en&state=2&force=true',
            \array_merge(['_hash' => \md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testPutWithAlreadyExistingUrl(): void
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $data['url'] = '/test2';

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $data['url'] = '/test';
        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $response['id'] . '?webspace=sulu_io&language=en&state=2&action=publish',
            $data
        );
        $this->assertHttpStatusCode(409, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(1103, $response['code']);
    }

    public function testHistory(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);
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

        \sleep(1);

        $this->client->jsonRequest('PUT', '/api/pages/' . $uuid . '?webspace=sulu_io&language=en&action=publish', $data);
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
        $this->client->jsonRequest('PUT', '/api/pages/' . $uuid . '?webspace=sulu_io&language=en&action=publish', $data);

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $uuid . '/resourcelocators?webspace=sulu_io&locale=en'
        );
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/a2', $response['_embedded']['page_resourcelocators'][0]['resourcelocator']);
        $this->assertEquals('/a1', $response['_embedded']['page_resourcelocators'][1]['resourcelocator']);
    }

    public function testTreeGetTillId(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'GET',
            '/api/pages?expandedIds=' . $data[2]['id'] . '&fields=title&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testTreeGetTillSelectedId(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'GET',
            '/api/pages?selectedIds=' . $data[2]['id'] . '&fields=title&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testTreeGetTillSelectedIdWithoutWebspace(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'GET',
            '/api/pages?selectedIds=' . $data[2]['id'] . '&fields=title&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

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

    public function testTreeGetTillIdWithLinkedProperty(): void
    {
        $externalLinkPage = $this->createPageDocument();
        $externalLinkPage->setTitle('page');
        $externalLinkPage->setStructureType('default');
        $externalLinkPage->setRedirectType(RedirectType::EXTERNAL);
        $externalLinkPage->setRedirectExternal('http://www.sulu.io');
        $externalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($externalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $internalLinkPage = $this->createPageDocument();
        $internalLinkPage->setTitle('page');
        $internalLinkPage->setStructureType('default');
        $internalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($internalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        /** @var BasePageDocument $internalLinkPage */
        $internalLinkPage = $this->documentManager->find($internalLinkPage->getUuid());
        $internalLinkPage->setRedirectType(RedirectType::INTERNAL);
        $internalLinkPage->setRedirectTarget($externalLinkPage);
        $this->documentManager->persist($internalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->client->jsonRequest(
            'GET',
            '/api/pages?expandedIds=' . $externalLinkPage->getUuid() . '&fields=title&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $this->client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent());

        $homepage = $response->_embedded->pages[0];

        // check if tree is correctly loaded till the given id
        $node1 = $homepage->_embedded->pages[0];
        $this->assertEquals('external', $node1->linked);

        $node2 = $homepage->_embedded->pages[1];
        $this->assertEquals('internal', $node2->linked);
    }

    public function testMove(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[4]['id'] . '?webspace=sulu_io&language=en&action=move&destination=' . $data[2]['id']
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

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

    public function testMoveNonExistingSource(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/123-123?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testMoveNonExistingDestination(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=123-123'
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCopy(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[4]['id'] . '?language=en&action=copy&destination=' . $data[2]['id']
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertNotEquals($data[4]['id'], $response['id']);
        $this->assertEquals('test5', $response['title']);
        $this->assertEquals('/test2/test3/test5', $response['path']);
        $this->assertEquals('/test2/test3/testing5', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertNull($response['published']);

        // check old node
        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $data[4]['id'] . '?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

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

    public function testCopyOtherWebspace(): void
    {
        $page = $this->createPageDocument();
        $page->setTitle('Testpage');
        $page->setResourceSegment('/testpage');
        $page->setStructureType('default');
        $this->documentManager->persist($page, 'fr', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->persist($page, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $destinationUuid = $this->session->getNode('/cmf/test_io/contents')->getIdentifier();

        $this->client->request(
            'POST',
            '/api/pages/' . $page->getUuid() . '?language=en&action=copy&destination=' . $destinationUuid
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertNotEquals($page->getUuid(), $response['id']);
        $this->assertEquals('Testpage', $response['title']);
        $this->assertEquals('/testpage', $response['path']);
        $this->assertEquals('/testpage', $response['url']);
        $this->assertEquals(false, $response['publishedState']);
        $this->assertNull($response['published']);

        // check old node
        $this->client->request(
            'GET',
            '/api/pages/' . $page->getUuid() . '?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($page->getUuid(), $response['id']);
        $this->assertEquals('Testpage', $response['title']);
        $this->assertEquals('/testpage', $response['path']);
        $this->assertEquals('/testpage', $response['url']);
    }

    public function testCopyWithShadow(): void
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

        /** @var BasePageDocument $document */
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

        $this->client->jsonRequest(
            'POST',
            \sprintf(
                '/api/pages/%s?webspace=sulu_io&language=en&action=copy&destination=%s',
                $document->getUuid(),
                $document->getUuid()
            )
        );

        $uuid = \json_decode($this->client->getResponse()->getContent(), true)['id'];

        /** @var BasePageDocument $germanDocument */
        $germanDocument = $this->documentManager->find($uuid, 'de');
        $this->assertStringStartsWith('/test-de/test-de', $germanDocument->getResourceSegment());

        /** @var BasePageDocument $englishDocument */
        $englishDocument = $this->documentManager->find($uuid, 'en');
        $this->assertStringStartsWith('/test-en/test-en', $englishDocument->getResourceSegment());
    }

    public function testCopyNonExistingSource(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/123-123?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testCopyNonExistingDestination(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=123-123'
        );
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testUnpublish(): void
    {
        $document = $this->createPageDocument();
        $document->setTitle('test_de');
        $document->setResourceSegment('/test_de');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        $this->client->jsonRequest(
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

    public function testRemoveDraft(): void
    {
        $document = $this->createPageDocument();
        $document->setTitle('published title');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($document->getUuid(), 'de');
        $document->setTitle('draft title');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('draft title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $document->getUuid() . '?action=remove-draft&webspace=sulu_io&language=de'
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('published title', $response['title']);

        $this->session->refresh(false);
        $this->liveSession->refresh(false);

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));
    }

    public function testRemoveDraftWithoutWebspace(): void
    {
        $document = $this->createPageDocument();
        $document->setTitle('published title');
        $document->setStructureType('default');
        $this->documentManager->persist($document, 'de', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'de');
        $this->documentManager->flush();

        /** @var BasePageDocument $document */
        $document = $this->documentManager->find($document->getUuid(), 'de');
        $document->setTitle('draft title');
        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $defaultNode = $this->session->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('draft title', $defaultNode->getPropertyValue('i18n:de-title'));
        $liveNode = $this->liveSession->getNodeByIdentifier($document->getUuid());
        $this->assertEquals('published title', $liveNode->getPropertyValue('i18n:de-title'));

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $document->getUuid() . '?action=remove-draft&language=de'
        );

        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testOrder(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/order.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[1]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals('/test2', $response['path']);
        $this->assertEquals('/test2', $response['url']);
        $this->assertEquals(30, $response['order']);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[3]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals('/test4', $response['path']);
        $this->assertEquals('/test4', $response['url']);
        $this->assertEquals(10, $response['order']);

        $this->client->jsonRequest('GET', '/api/pages?fields=title,order&webspace=sulu_io&language=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $homepage = $response['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertEquals(4, \count($items));
        $this->assertEquals('test4', $items[0]['title']);
        $this->assertEquals(10, $items[0]['order']);
        $this->assertEquals('test1', $items[1]['title']);
        $this->assertEquals(20, $items[1]['order']);
        $this->assertEquals('test3', $items[2]['title']);
        $this->assertEquals(30, $items[2]['order']);
        $this->assertEquals('test2', $items[3]['title']);
        $this->assertEquals(40, $items[3]['order']);
    }

    public function testOrderWithGhosts(): void
    {
        $data = $this->importer->import(__DIR__ . '/../../fixtures/exports/order.xml');

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[1]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals(30, $response['order']);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[3]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals(10, $response['order']);

        $this->client->jsonRequest('GET', '/api/pages?fields=title,order&webspace=sulu_io&language=de');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $homepage = $response['_embedded']['pages'][0];
        $items = $homepage['_embedded']['pages'];

        $this->assertEquals(4, \count($items));
        $this->assertEquals('test4', $items[0]['title']);
        $this->assertEquals(10, $items[0]['order']);
        $this->assertEquals('test1', $items[1]['title']);
        $this->assertEquals(20, $items[1]['order']);
        $this->assertEquals('test3', $items[2]['title']);
        $this->assertEquals(30, $items[2]['order']);
        $this->assertEquals('test2', $items[3]['title']);
        $this->assertEquals(40, $items[3]['order']);
    }

    public function testOrderNonExistingSource(): void
    {
        $this->client->jsonRequest(
            'POST',
            '/api/pages/123-123-123?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testOrderNonExistingPosition(): void
    {
        $data = [
            [
                'title' => 'test1',
                'template' => 'default',
                'url' => '/test1',
            ],
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data[0]
        );
        $data[0] = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 42,
            ]
        );
        $this->assertHttpStatusCode(400, $this->client->getResponse());
    }

    public function testNavContexts(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('test1', $data['title']);
        $this->assertEquals('/test1', $data['path']);
        $this->assertEquals(1, $data['nodeState']);
        $this->assertFalse($data['publishedState']);
        $this->assertEquals(['main', 'footer'], $data['navContexts']);
        $this->assertFalse($data['hasSub']);

        $this->client->jsonRequest('GET', '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test1', $response['path']);
        $this->assertFalse($response['publishedState']);
        $this->assertEquals(['main', 'footer'], $response['navContexts']);
        $this->assertFalse($response['hasSub']);
    }

    public function testSegment(): void
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'tags' => [
                'tag1',
            ],
            'url' => '/test1',
            'article' => 'Test',
            'ext' => [
                'excerpt' => [
                    'segments' => [
                        'sulu_io' => 's',
                    ],
                ],
            ],
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->request(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('test1', $data['title']);
        $this->assertEquals('s', $data['ext']['excerpt']['segments']['sulu_io']);

        $this->client->request('GET', '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $response = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('s', $data['ext']['excerpt']['segments']['sulu_io']);
    }

    public function testPostTriggerAction(): void
    {
        $webspaceUuid = $this->session->getNode('/cmf/sulu_io/contents')->getIdentifier();

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $webspaceUuid . '?webspace=sulu_io&action=copy-locale&locale=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
    }

    public function testCopyLocale(): void
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $result['availableLocales']);
        $this->assertContains('de', $result['availableLocales']);
        $this->assertContains('en', $result['availableLocales']);
        $this->assertCount(2, $result['contentLocales']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertEquals($data['article'], $result['article']);
        $this->assertCount(2, $result['availableLocales']);
        $this->assertContains('de', $result['availableLocales']);
        $this->assertContains('en', $result['availableLocales']);
        $this->assertCount(2, $result['contentLocales']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);
    }

    public function testCopyLocaleWithSource(): void
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=de&src=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertEquals($data['article'], $result['article']);
        $this->assertFalse($result['publishedState']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertEquals($data['article'], $result['article']);
        $this->assertFalse($result['publishedState']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);
    }

    public function testCopyLocaleWithNoDest(): void
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en&action=publish',
            $data
        );
        /** @var array<string, mixed> $data */
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&locale=en&dest='
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        /** @var array<string, mixed> $result */
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($data['id'], $result['id'] ?? null);
        $this->assertSame($data['title'], $result['title'] ?? null);
        $this->assertSame($data['url'], $result['url'] ?? null);
        $this->assertSame($data['article'], $result['article'] ?? null);
        $this->assertTrue($result['publishedState'] ?? null);
        $this->assertSame(['en'], $result['contentLocales'] ?? null);
    }

    public function testCopyMultipleLocales(): void
    {
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data
        );
        $data = \json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest(
            'POST',
            '/api/pages/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de,de_at'
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        /** @var array<string, mixed> $result */
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);

        $this->client->jsonRequest(
            'GET',
            '/api/pages/' . $data['id'] . '?webspace=sulu_io&language=de_at'
        );
        $result = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['contentLocales']);
        $this->assertContains('en', $result['contentLocales']);
    }

    public function testInternalLinkAutoName(): void
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

        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        $this->client->jsonRequest(
            'POST',
            '/api/pages?parentId=' . $homeDocument->getUuid() . '&webspace=sulu_io&language=en',
            $data[0]
        );
        $data[0] = \json_decode($this->client->getResponse()->getContent(), true);
        $this->client->jsonRequest('POST', '/api/pages?webspace=sulu_io&language=en&parentId=' . $data[0]['id'], $data[1]);
        $data[1] = \json_decode($this->client->getResponse()->getContent(), true);

        $data[0]['internalLinks'][] = $data[1]['id'];
        $this->client->jsonRequest('PUT', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $data[0] = \json_decode($this->client->getResponse()->getContent(), true);

        $data[0]['title'] = 'Dornbirn';
        $this->client->jsonRequest('PUT', '/api/pages/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $result = \json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('/dornbirn', $result['path']);
        $this->assertEquals('Dornbirn', $result['title']);
    }

    public function testRenamePageWithLinkedChild(): void
    {
        $this->importer->import(__DIR__ . '/../../fixtures/exports/tree.xml');

        /** @var BasePageDocument $document */
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

        $this->client->jsonRequest('GET', '/api/pages/' . $document->getUuid() . '?webspace=sulu_io&language=en');
        $data = \json_decode($this->client->getResponse()->getContent(), true);
        $data['title'] = 'Sulu is awesome';

        $this->client->jsonRequest(
            'PUT',
            '/api/pages/' . $document->getUuid() . '?webspace=sulu_io&language=en&action=publish',
            $data
        );

        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $data = \json_decode($this->client->getResponse()->getContent(), true);

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

    public function testDeleteReferencedNode(): void
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

        $this->client->jsonRequest('DELETE', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en');

        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(409, $response);

        $content = \json_decode((string) $response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('errors', $content);
        unset($content['errors']);

        $this->assertEquals([
            'code' => 1106,
            'message' => 'Found 1 referencing resources.',
            'resource' => [
                'id' => $linkedDocument->getUuid(),
                'resourceKey' => 'pages',
            ],
            'referencingResourcesCount' => 1,
            'referencingResources' => [
                [
                    'id' => $document->getUuid(),
                    'resourceKey' => 'pages',
                    'title' => 'test2',
                ],
            ],
        ], $content);

        $this->client->jsonRequest('DELETE', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en&force=true');
        $this->assertHttpStatusCode(204, $this->client->getResponse());

        $this->client->jsonRequest('GET', '/api/pages/' . $linkedDocument->getUuid() . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    /**
     * @return PageDocument
     */
    private function createPageDocument()
    {
        return $this->documentManager->create('page');
    }

    private function createRole(string $name = 'Role', string $system = 'Website')
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->em->persist($role);

        return $role;
    }

    private function setUpContent($data)
    {
        /** @var BasePageDocument $homeDocument */
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');

        for ($i = 0; $i < \count($data); ++$i) {
            $pageData = $data[$i];

            $this->client->jsonRequest(
                'POST',
                '/api/pages?parentId=' . ($pageData['parentUuid'] ?? $homeDocument->getUuid()) . '&webspace=sulu_io&language=en',
                $pageData
            );

            $data[$i] = (array) \json_decode($this->client->getResponse()->getContent(), true);
        }

        return $data;
    }
}
