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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPCR\NodeInterface;
use PHPCR\SessionInterface;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * @group nodecontroller
 */
class NodeControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->session = $this->getContainer()->get('doctrine_phpcr')->getConnection();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->initOrm();
        $this->initPhpcr();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();

        $tag1 = new Tag();

        $metadata = $this->em->getClassMetaData(get_class($tag1));
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $tag1->setId(1);
        $tag1->setName('tag1');
        $this->em->persist($tag1);
        $this->em->flush();

        $tag2 = new Tag();
        $tag2->setId(2);
        $tag2->setName('tag2');
        $this->em->persist($tag2);
        $this->em->flush();

        $tag3 = new Tag();
        $tag3->setId(3);
        $tag3->setName('tag3');
        $this->em->persist($tag3);
        $this->em->flush();

        $tag4 = new Tag();
        $tag4->setId(4);
        $tag4->setName('tag4');
        $this->em->persist($tag4);
        $this->em->flush();
    }

    public function testPost()
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

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Testtitle', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/test', $response->url);
        $this->assertEquals(['tag1', 'tag2'], $response->tags);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->changer);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/en/test');

        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testtitle', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertCount(2, $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-changer'));
    }

    public function testPostTree()
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

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data1);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $uuid = $response->id;

        $client->request(
            'POST',
            '/api/nodes?parent=' . $uuid . '&webspace=sulu_io&language=en',
            $data2
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('test-1', $response->title);
        $this->assertEquals('Test', $response->article);
        $this->assertEquals('/news/test', $response->url);
        $this->assertEquals(['tag1', 'tag2'], $response->tags);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->changer);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/en/news/test');

        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('test-1', $content->getProperty('i18n:en-title')->getString());
        $this->assertEquals('Test', $content->getProperty('i18n:en-article')->getString());
        $this->assertCount(2, $content->getPropertyValue('i18n:en-tags'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-creator'));
        $this->assertEquals($this->getTestUserId(), $content->getPropertyValue('i18n:en-changer'));

        // check parent
        $this->assertEquals($uuid, $content->getParent()->getIdentifier());
    }

    public function testPostWithExistingResourceLocator()
    {
        $client = $this->createAuthenticatedClient();

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

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(409, $client->getResponse());
    }

    public function testGet()
    {
        $client = $this->createAuthenticatedClient();

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

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('seo', $response['ext']);
        $this->assertArrayHasKey('excerpt', $response['ext']);

        $this->assertEquals($data[0]['title'], $response['title']);
        $this->assertEquals($data[0]['path'], $response['path']);
        $this->assertEquals($data[0]['tags'], $response['tags']);
        $this->assertEquals($data[0]['url'], $response['url']);
        $this->assertEquals($data[0]['article'], $response['article']);

        $this->assertEquals('/api/nodes/' . $data[0]['id'] . '?language=en', $response['_links']['self']['href']);
        $this->assertEquals(
            '/api/nodes?parent=' . $data[0]['id'] . '&depth=1&webspace=sulu_io&language=en',
            $response['_links']['children']['href']
        );
    }

    public function testGetNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/not-existing-id?language=en');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testGetLocalized()
    {
        $data = [
            'en' => [
                'template' => 'default',
                'title' => 'test_en',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_en',
                'article' => 'Test English',
            ],
            'de' => [
                'template' => 'default',
                'title' => 'test_de',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_de',
                'article' => 'Test German',
            ],
        ];
        $mapper = $this->getMapper();

        $englishStructure = $mapper->save($data['en'], 'default', 'sulu_io', 'en', 1);
        $germanStructure = $mapper->save(
            $data['de'],
            'default',
            'sulu_io',
            'de',
            1,
            true,
            $englishStructure->getUuid()
        );

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/' . $englishStructure->getUuid() . '?language=en');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['en']['title'], $response['title']);

        $client->request('GET', '/api/nodes/' . $germanStructure->getUuid() . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['de']['title'], $response['title']);
    }

    public function testGetGhostContent()
    {
        $data = [
            'en' => [
                'template' => 'default',
                'title' => 'test_en',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_en',
                'article' => 'Test English',
            ],
        ];
        $mapper = $this->getMapper();

        $englishStructure = $mapper->save($data['en'], 'default', 'sulu_io', 'en', 1);

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/' . $englishStructure->getUuid() . '?language=de&ghost-content=true');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['en']['title'], $response['title']);
        $this->assertEquals(['name' => 'ghost', 'value' => 'en'], $response['type']);

        $client->request('GET', '/api/nodes/' . $englishStructure->getUuid() . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('', $response['title']);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testGetShadowContent()
    {
        $data = [
            'en' => [
                'template' => 'default',
                'title' => 'test_en',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_en',
                'article' => 'Test English',
            ],
            'de' => [
                'template' => 'default',
                'title' => 'test_de',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_de',
                'article' => 'Test German',
            ],
        ];
        $mapper = $this->getMapper();

        $englishStructure = $mapper->save($data['en'], 'default', 'sulu_io', 'en', 1);
        $germanStructure = $mapper->save(
            $data['de'],
            'default',
            'sulu_io',
            'de',
            1,
            true,
            $englishStructure->getUuid()
        );

        $germanStructure = $mapper->save(
            $data['de'],
            'default',
            'sulu_io',
            'de',
            1,
            true,
            $germanStructure->getUuid(),
            null,
            null,
            true,
            'en'
        );

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/' . $germanStructure->getUuid() . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data['en']['title'], $response['title']);
        $this->assertEquals($data['en']['article'], $response['article']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);
        $this->assertEquals(['en' => 'de'], $response['enabledShadowLanguages']);
        $this->assertEquals(true, $response['shadowOn']);
    }

    public function testGetInternalLink()
    {
        $client = $this->createAuthenticatedClient();

        $targetPage = $this->documentManager->create('page');
        $targetPage->setTitle('target');
        $targetPage->setResourceSegment('/target');
        $targetPage->setStructureType('default');
        $this->documentManager->persist($targetPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);

        $this->documentManager->flush();

        $internalLinkPage = $this->documentManager->create('page');
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

        $client->request('GET', '/api/nodes/' . $internalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('internal', $response['linked']);
        $this->assertEquals($targetPage->getUuid(), $response['internal_link']);
    }

    public function testGetExternalLink()
    {
        $client = $this->createAuthenticatedClient();

        $externalLinkPage = $this->documentManager->create('page');
        $externalLinkPage->setTitle('page');
        $externalLinkPage->setStructureType('external-link');
        $externalLinkPage->setRedirectType(RedirectType::EXTERNAL);
        $externalLinkPage->setRedirectExternal('http://www.sulu.io');
        $externalLinkPage->setResourceSegment('/test');
        $this->documentManager->persist($externalLinkPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);

        $this->documentManager->flush();

        $client->request('GET', '/api/nodes/' . $externalLinkPage->getUuid() . '?webspace=sulu_io&language=en');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('external', $response['linked']);
        $this->assertEquals('http://www.sulu.io', $response['external']);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

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

        $client->request('DELETE', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testDeleteReferencedNode()
    {
        $client = $this->createAuthenticatedClient();

        $deleteData = [
            [
                'template' => 'simple',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ];

        $deleteData = $this->setUpContent($deleteData);

        $linkData = [
            [
                'template' => 'internallinks',
                'title' => 'test2',
                'url' => '/test2',
                'internalLinks' => [
                    $deleteData[0]['id'],
                ],
            ],
        ];

        $linkData = $this->setupContent($linkData);

        $client->request('DELETE', '/api/nodes/' . $deleteData[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(409, $client->getResponse());
    }

    public function testDeleteReferencedNodeWithForce()
    {
        $client = $this->createAuthenticatedClient();

        $deleteData = [
            [
                'template' => 'simple',
                'title' => 'test1',
                'url' => '/test1',
            ],
        ];

        $deleteData = $this->setUpContent($deleteData);

        $linkData = [
            [
                'template' => 'internallinks',
                'title' => 'test2',
                'url' => '/test2',
                'internalLinks' => [
                    $deleteData[0]['id'],
                ],
            ],
        ];

        $linkData = $this->setupContent($linkData);

        $client->request('DELETE', '/api/nodes/' . $deleteData[0]['id'] . '?webspace=sulu_io&language=en&force=true');
        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request('GET', '/api/nodes/' . $deleteData[0]['id'] . '?webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

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

        $data[0]['title'] = 'test123';
        $data[0]['tags'] = ['new tag'];
        $data[0]['article'] = 'thats a new article';

        $client->request(
            'PUT',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($data[0]['title'], $response->title);
        $this->assertEquals($data[0]['tags'], $response->tags);
        $this->assertEquals($data[0]['url'], $response->url);
        $this->assertEquals($data[0]['article'], $response->article);
        $this->assertEquals($this->getTestUserId(), $response->creator);
        $this->assertEquals($this->getTestUserId(), $response->creator);

        $this->assertEquals(2, count((array) $response->ext));

        $this->assertEquals(7, count((array) $response->ext->seo));
        $this->assertEquals(7, count((array) $response->ext->excerpt));

        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals(2, count($response->_embedded->nodes));

        $this->assertEquals($data[0]['title'], $response->_embedded->nodes[0]->title);
        $this->assertEquals($data[1]['title'], $response->_embedded->nodes[1]->title);
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

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $data = [
            'template' => 'default',
            'title' => 'Test',
        ];

        $client->request('GET', '/api/nodes?webspace=sulu_io&language=en');
        $response = json_decode($client->getResponse()->getContent());

        $client->request(
            'PUT',
            '/api/nodes/' . $response->id . '?webspace=sulu_io&language=en',
            $data
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);

        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals($data['title'], $response->title);
    }

    public function testPutNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/nodes/not-existing-id?language=de', []);
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testPutWithTemplateChange()
    {
        $client = $this->createAuthenticatedClient();

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

        $client->request(
            'PUT',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en',
            $data[0]
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('default', $response->template);
        $this->assertEquals('article test', $response->article);
    }

    public function testPutWithLanguage()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=de',
            [
                'title' => 'Testtitle DE',
                'template' => 'default',
                'url' => '/test-de',
            ]
        );
        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=en',
            [
                'title' => 'Testtitle EN',
                'template' => 'default',
                'url' => '/test-en',
            ]
        );

        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Testtitle DE', $response['title']);
        $this->assertEquals('/test-de', $response['url']);

        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=en');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Testtitle EN', $response['title']);
        $this->assertEquals('/test-en', $response['url']);
    }

    public function testPutShadow()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request('PUT', '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=de', $data);
        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=de',
            array_merge($data, ['shadowOn' => true, 'shadowBaseLanguage' => 'en'])
        );
        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('en', $response['shadowBaseLanguage']);
        $this->assertEquals('shadow', $response['type']['name']);
        $this->assertEquals('en', $response['type']['value']);

        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=de',
            array_merge($data, ['shadowOn' => false, 'shadowBaseLanguage' => null])
        );
        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals(null, $response['shadowBaseLanguage']);
        $this->assertArrayNotHasKey('type', $response);
        $this->assertArrayNotHasKey('type', $response);
    }

    public function testPutRemoveShadowWithDifferentTemplate()
    {
        $data = [
            'en' => [
                'template' => 'default',
                'title' => 'test_en',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_en',
                'article' => 'Test English',
            ],
            'de' => [
                'template' => 'overview',
                'title' => 'test_de',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/test_de',
                'article' => 'Test German',
            ],
        ];
        $mapper = $this->getMapper();

        $englishStructure = $mapper->save($data['en'], 'default', 'sulu_io', 'en', 1);
        $germanStructure = $mapper->save(
            $data['de'],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            $englishStructure->getUuid()
        );

        $germanStructure = $mapper->save(
            $data['de'],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            $germanStructure->getUuid(),
            null,
            null,
            true,
            'en'
        );

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/' . $germanStructure->getUuid() . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(true, $response['shadowOn']);
        $this->assertEquals('default', $response['template']);

        $client->request(
            'PUT',
            '/api/nodes/' . $germanStructure->getUuid() . '?language=de&webspace=sulu_io',
            [
                'id' => $germanStructure->getUuid(),
                'nodeType' => 1,
                'shadowOn' => false,
            ]
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/nodes/' . $germanStructure->getUuid() . '?language=de');
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(false, $response['shadowOn']);
        $this->assertEquals('overview', $response['template']);
    }

    public function testPutState()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['nodeState']);

        $client->request('PUT', '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=en&state=2', $data);
        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(2, $response['nodeState']);

        $client->request('PUT', '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=en&state=1', $data);
        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $response['nodeState']);
    }

    public function testPutWithValidHash()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request('GET', '/api/nodes/' . $response['id'] . '?language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=en&state=2',
            array_merge(['_hash' => $response['_hash']], $data)
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testPutWithInvalidHash()
    {
        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];

        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
        $id = $response['id'];

        $client->request(
            'PUT',
            '/api/nodes/' . $id . '?webspace=sulu_io&language=en&state=2',
            array_merge(['_hash' => md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(409, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1102, $response['code']);

        $client->request(
            'PUT',
            '/api/nodes/' . $id . '?webspace=sulu_io&language=en&state=2&force=true',
            array_merge(['_hash' => md5('wrong-hash')], $data)
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
    }

    public function testPutWithAlreadyExistingUrl()
    {
        $client = $this->createAuthenticatedClient();

        $data = [
            'title' => 'Testtitle',
            'template' => 'default',
            'url' => '/test',
        ];
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $client->getResponse());

        $data['url'] = '/test2';
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $data['url'] = '/test';
        $client->request(
            'PUT',
            '/api/nodes/' . $response['id'] . '?webspace=sulu_io&language=en&state=2',
            $data
        );
        $this->assertHttpStatusCode(409, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1103, $response['code']);
    }

    public function testTreeGet()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));
        $this->assertEquals($data[0]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals($data[1]['title'], $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children->href));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));
        $this->assertEquals($data[2]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals($data[3]['title'], $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get subitems (remove /admin for test environment)
        $client->request('GET', str_replace('/admin', '', $items[1]->_links->children->href));
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, count($items));
        $this->assertEquals($data[4]['title'], $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
    }

    public function testTreeGetTillId()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'GET',
            '/api/nodes?id=' . $data[2]['id'] . '&tree=true&webspace=sulu_io&language=en&exclude-ghosts=false'
        );

        $response = $client->getResponse()->getContent();
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());

        // check if tree is correctly loaded till the given id
        $node1 = $response->_embedded->nodes[0];
        $this->assertEquals($data[0]['path'], $node1->path);
        $this->assertFalse($node1->hasSub);
        $this->assertEmpty($node1->_embedded->nodes);

        $node2 = $response->_embedded->nodes[1];
        $this->assertEquals($data[1]['path'], $node2->path);
        $this->assertTrue($node2->hasSub);
        $this->assertCount(2, $node2->_embedded->nodes);

        $node3 = $node2->_embedded->nodes[0];
        $this->assertEquals($data[2]['path'], $node3->path);
        $this->assertFalse($node3->hasSub);
        $this->assertCount(0, $node3->_embedded->nodes);

        $node4 = $node2->_embedded->nodes[1];
        $this->assertEquals($data[3]['path'], $node4->path);
        $this->assertTrue($node4->hasSub);
        $this->assertCount(0, $node4->_embedded->nodes);
    }

    public function testGetFlat()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=2&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(4, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        $this->assertEquals('test3', $items[2]->title);
        $this->assertFalse($items[2]->hasSub);

        $this->assertEquals('test4', $items[3]->title);
        $this->assertTrue($items[3]->hasSub);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=3&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(5, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);

        $this->assertEquals('test3', $items[2]->title);
        $this->assertFalse($items[2]->hasSub);

        $this->assertEquals('test4', $items[3]->title);
        $this->assertTrue($items[3]->hasSub);

        $this->assertEquals('test5', $items[4]->title);
        $this->assertFalse($items[4]->hasSub);

        // get child nodes from subNode
        $client->request('GET', '/api/nodes?depth=3&webspace=sulu_io&language=en&parent=' . $data[3]['id']);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, count($items));

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
    }

    public function testGetTree()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&flat=false&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(0, count($items[1]->_embedded->nodes));

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=2&flat=false&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(2, count($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test3', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        $this->assertEquals('test4', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(0, count($items[1]->_embedded->nodes));

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=3&flat=false&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(2, count($items));

        $this->assertEquals('test1', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        $this->assertEquals('test2', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(2, count($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test3', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        $this->assertEquals('test4', $items[1]->title);
        $this->assertTrue($items[1]->hasSub);
        $this->assertEquals(1, count($items[1]->_embedded->nodes));

        $items = $items[1]->_embedded->nodes;

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));

        // get child nodes from subNode
        $client->request('GET', '/api/nodes?depth=3&flat=false&webspace=sulu_io&language=en&parent=' . $data[3]['id']);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent());
        $items = $response->_embedded->nodes;

        $this->assertEquals(1, count($items));

        $this->assertEquals('test5', $items[0]->title);
        $this->assertFalse($items[0]->hasSub);
        $this->assertEquals(0, count($items[0]->_embedded->nodes));
    }

    public function testSmartContent()
    {
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(5, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id']);
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(2, count($items));
        $this->assertEquals($data[1]['title'], $response['title']);

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true'
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(3, count($items));
        $this->assertEquals($data[1]['title'], $response['title']);

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true&limitResult=2'
        );
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(2, count($items));
        $this->assertEquals($data[1]['title'], $response['title']);

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(4, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag2');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(3, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1,tag2&tagOperator=and');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(2, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1,tag2&tagOperator=or');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(5, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1,tag2,tag3&tagOperator=and');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(0, count($items));

        $client->request('GET', '/api/nodes/filter?webspace=sulu_io&language=en&tags=tag1,tag2,tag3&tagOperator=or');
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals('Homepage', $response['title']);
        $this->assertEquals(5, count($items));

        $client->request(
            'GET',
            '/api/nodes/filter?webspace=sulu_io&language=en&dataSource=' . $data[1]['id'] . '&includeSubFolders=true&limitResult=2&sortBy=title'
        );
        $response = json_decode($client->getResponse()->getContent(), true);
    }

    public function testBreadcrumb()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request('GET', '/api/nodes/' . $data[4]['id'] . '?breadcrumb=true&webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data[4]['title'], $response['title']);
        $this->assertEquals($data[4]['url'], $response['url']);
        $this->assertEquals($data[4]['article'], $response['article']);

        $this->assertEquals(3, count($response['breadcrumb']));
        $this->assertEquals('Homepage', $response['breadcrumb'][0]['title']);
        $this->assertEquals(0, $response['breadcrumb'][0]['depth']);
        $this->assertEquals('test2', $response['breadcrumb'][1]['title']);
        $this->assertEquals(1, $response['breadcrumb'][1]['depth']);
        $this->assertEquals('test4', $response['breadcrumb'][2]['title']);
        $this->assertEquals(2, $response['breadcrumb'][2]['depth']);

        $client->request('GET', '/api/nodes/' . $data[4]['id'] . '?breadcrumb=false&webspace=sulu_io&language=en');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data[4]['title'], $response['title']);
        $this->assertEquals($data[4]['url'], $response['url']);
        $this->assertEquals($data[4]['article'], $response['article']);

        $this->assertArrayNotHasKey('breadcrumb', $response);
    }

    public function testSmallResponse()
    {
        $client = $this->createAuthenticatedClient();

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

        $client->request('GET', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&complete=false');

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('title', $response);
        $this->assertArrayHasKey('path', $response);
        $this->assertArrayHasKey('nodeType', $response);
        $this->assertArrayHasKey('nodeState', $response);
        $this->assertArrayHasKey('internal', $response);
        $this->assertArrayHasKey('concreteLanguages', $response);
        $this->assertArrayHasKey('hasSub', $response);
        $this->assertArrayHasKey('order', $response);
        $this->assertArrayHasKey('linked', $response);
        $this->assertArrayHasKey('publishedState', $response);
        $this->assertArrayHasKey('published', $response);
        $this->assertArrayHasKey('navContexts', $response);
        $this->assertArrayNotHasKey('article', $response);
        $this->assertArrayNotHasKey('tags', $response);
        $this->assertArrayNotHasKey('ext', $response);
        $this->assertArrayNotHasKey('enabledShadowLanguage', $response);
        $this->assertArrayHasKey('concreteLanguages', $response);
        $this->assertArrayNotHasKey('shadowOn', $response);
        $this->assertArrayNotHasKey('shadowBaseLanguage', $response);
    }

    public function testCgetAction()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(2, count($items));

        $this->assertArrayHasKey('id', $items[0]);
        $this->assertEquals('test1', $items[0]['title']);
        $this->assertEquals('/test1', $items[0]['path']);
        $this->assertEquals(2, $items[0]['nodeState']);
        $this->assertTrue($items[0]['publishedState']);
        $this->assertEmpty($items[0]['navContexts']);
        $this->assertFalse($items[0]['hasSub']);
        $this->assertEquals(0, count($items[0]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[0]);

        $this->assertArrayHasKey('id', $items[1]);
        $this->assertEquals('test2', $items[1]['title']);
        $this->assertEquals('/test2', $items[1]['path']);
        $this->assertEquals(2, $items[1]['nodeState']);
        $this->assertTrue($items[1]['publishedState']);
        $this->assertEmpty($items[1]['navContexts']);
        $this->assertTrue($items[1]['hasSub']);
        $this->assertEquals(0, count($items[1]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[1]);
    }

    public function testHistory()
    {
        $client = $this->createAuthenticatedClient();
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
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $response = json_decode($client->getResponse()->getContent(), true);
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

        $client->request('PUT', '/api/nodes/' . $uuid . '?webspace=sulu_io&language=en', $data);
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
        $client->request('PUT', '/api/nodes/' . $uuid . '?webspace=sulu_io&language=en', $data);

        $client->request(
            'GET',
            '/api/nodes/' . $uuid . '/resourcelocators?webspace=sulu_io&language=en'
        );
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('/a2', $response['_embedded']['resourcelocators'][0]['resourceLocator']);
        $this->assertEquals('/a1', $response['_embedded']['resourcelocators'][1]['resourceLocator']);
    }

    public function testMove()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[0]['id'], $response['id']);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test2/test1', $response['path']);
        $this->assertEquals('/test2/test1', $response['url']);
    }

    public function testMoveNonExistingSource()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/123-123?webspace=sulu_io&language=en&action=move&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testMoveNonExistingDestination()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=move&destination=123-123'
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testCopy()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertNotEquals($data[0]['id'], $response['id']);
        $this->assertEquals('test1', $response['title']);
        $this->assertEquals('/test2/test1', $response['path']);
        $this->assertEquals('/test2/test1', $response['url']);

        // check old node
        $client->request(
            'GET',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals($data[0]['id'], $response['id']);
        $this->assertEquals($data[0]['title'], $response['title']);
        $this->assertEquals($data[0]['path'], $response['path']);
        $this->assertEquals($data[0]['template'], $response['template']);
        $this->assertEquals($data[0]['url'], $response['url']);
        $this->assertEquals($data[0]['article'], $response['article']);
    }

    public function testCopyNonExistingSource()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/123-123?webspace=sulu_io&language=en&action=copy&destination=' . $data[1]['id']
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testCopyNonExistingDestination()
    {
        $client = $this->createAuthenticatedClient();
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/tree.xml');

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=copy&destination=123-123'
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testOrder()
    {
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/order.xml');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/nodes/' . $data[1]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals('/test2', $response['path']);
        $this->assertEquals('/test2', $response['url']);
        $this->assertEquals(30, $response['order']);

        $client->request(
            'POST',
            '/api/nodes/' . $data[3]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals('/test4', $response['path']);
        $this->assertEquals('/test4', $response['url']);
        $this->assertEquals(10, $response['order']);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

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
        $data = $this->importer->import(__DIR__ . '/../../app/Resources/exports/order.xml');

        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/nodes/' . $data[1]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 3,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[1]['id'], $response['id']);
        $this->assertEquals('test2', $response['title']);
        $this->assertEquals(30, $response['order']);

        $client->request(
            'POST',
            '/api/nodes/' . $data[3]['id'] . '?webspace=sulu_io&language=de&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        // check some properties
        $this->assertEquals($data[3]['id'], $response['id']);
        $this->assertEquals('test4', $response['title']);
        $this->assertEquals(10, $response['order']);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=de');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

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
        $data = [
            [
                'title' => 'test1',
                'url' => '/test1',
            ],
        ];

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/123-123-123?webspace=sulu_io&language=en&action=order',
            [
                'position' => 1,
            ]
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
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

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en&action=order',
            [
                'position' => 42,
            ]
        );
        $this->assertHttpStatusCode(400, $client->getResponse());
    }

    public function testNavContexts()
    {
        $client = $this->createAuthenticatedClient();
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
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('test1', $data['title']);
        $this->assertEquals('/test1', $data['path']);
        $this->assertEquals(1, $data['nodeState']);
        $this->assertFalse($data['publishedState']);
        $this->assertEquals(['main', 'footer'], $data['navContexts']);
        $this->assertFalse($data['hasSub']);
        $this->assertEquals(0, count($data['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $data);

        // get child nodes from root
        $client->request('GET', '/api/nodes?depth=1&webspace=sulu_io&language=en');
        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);
        $items = $response['_embedded']['nodes'];

        $this->assertEquals(1, count($items));

        $this->assertArrayHasKey('id', $items[0]);
        $this->assertEquals('test1', $items[0]['title']);
        $this->assertEquals('/test1', $items[0]['path']);
        $this->assertEquals(1, $items[0]['nodeState']);
        $this->assertFalse($items[0]['publishedState']);
        $this->assertEquals(['main', 'footer'], $items[0]['navContexts']);
        $this->assertFalse($items[0]['hasSub']);
        $this->assertEquals(0, count($items[0]['_embedded']['nodes']));
        $this->assertArrayHasKey('_links', $items[0]);
    }

    public function testCopyLocale()
    {
        $client = $this->createAuthenticatedClient();
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $data = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/nodes/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertEquals($data['article'], $result['article']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

    public function testCopyMultipleLocales()
    {
        $client = $this->createAuthenticatedClient();
        $data = [
            'title' => 'test1',
            'template' => 'default',
            'url' => '/test1',
            'article' => 'Test',
        ];
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data);
        $data = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'POST',
            '/api/nodes/' . $data['id'] . '?action=copy-locale&webspace=sulu_io&language=en&dest=de,de_at'
        );
        $this->assertHttpStatusCode(200, $client->getResponse());

        $client->request(
            'GET',
            '/api/nodes/' . $data['id'] . '?webspace=sulu_io&language=de'
        );
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);

        $client->request(
            'GET',
            '/api/nodes/' . $data['id'] . '?webspace=sulu_io&language=de_at'
        );
        $result = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['title'], $result['title']);
        $this->assertEquals($data['url'], $result['url']);
        $this->assertContains('de', $result['concreteLanguages']);
        $this->assertContains('en', $result['concreteLanguages']);
    }

    public function testGetWithPermissions()
    {
        // create secured page
        $securedPage = $this->documentManager->create('page');
        $securedPage->setTitle('secured');
        $securedPage->setResourceSegment('/secured');
        $securedPage->setStructureType('default');
        $this->documentManager->persist($securedPage, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/nodes?uuid=' . $securedPage->getUuid() . '&tree=true&webspace=sulu_io&language=en'
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response['_embedded']['nodes'][0]['_embedded'][0]);

        $client->request('GET', '/api/nodes/' . $securedPage->getUuid() . '?language=en&webspace=sulu_io');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('_permissions', $response);
    }

    public function testCGetWithAllWebspaceNodes()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/nodes?webspace=sulu_io&language=de&fields=title&webspace-nodes=all'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $nodes = $response['_embedded']['nodes'];
        $this->assertCount(2, $nodes);

        $titles = array_map(
            function ($node) {
                return $node['title'];
            },
            $nodes
        );
        $this->assertContains('Sulu CMF', $titles);
        $this->assertContains('Test CMF', $titles);
    }

    public function testCGetWithAllWebspaceNodesDifferentLocales()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/nodes?webspace=sulu_io&language=fr&fields=title&webspace-nodes=all'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $nodes = $response['_embedded']['nodes'];
        $this->assertCount(1, $nodes);

        $titles = array_map(
            function ($node) {
                return $node['title'];
            },
            $nodes
        );
        $this->assertContains('Sulu CMF', $titles);
    }

    public function testCGetWithSingleWebspaceNodes()
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'GET',
            '/api/nodes?webspace=sulu_io&language=fr&fields=title&webspace-nodes=single'
        );

        $this->assertHttpStatusCode(200, $client->getResponse());
        $response = json_decode($client->getResponse()->getContent(), true);

        $nodes = $response['_embedded']['nodes'];
        $this->assertCount(1, $nodes);

        $titles = array_map(
            function ($node) {
                return $node['title'];
            },
            $nodes
        );
        $this->assertContains('Sulu CMF', $titles);
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

        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);
        $client->request('POST', '/api/nodes?webspace=sulu_io&language=en&parent=' . $data[0]['id'], $data[1]);
        $data[1] = json_decode($client->getResponse()->getContent(), true);

        $data[0]['internalLinks'][] = $data[1]['id'];
        $client->request('PUT', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $data[0] = json_decode($client->getResponse()->getContent(), true);

        $data[0]['title'] = 'Dornbirn';
        $client->request('PUT', '/api/nodes/' . $data[0]['id'] . '?webspace=sulu_io&language=en', $data[0]);
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('/dornbirn', $result['path']);
        $this->assertEquals('Dornbirn', $result['title']);
    }

    private function setUpContent($data)
    {
        /** @var ContentMapperInterface $mapper */
        $mapper = $this->getMapper();

        $mapper->save(
            ['title' => 'Start Page'],
            'default',
            'sulu_io',
            'de',
            1,
            true,
            $this->getContainer()->get('sulu.phpcr.session')->getContentNode('sulu_io')->getIdentifier(),
            null,
            WorkflowStage::PUBLISHED,
            null,
            null,
            'home'
        );

        $client = $this->createAuthenticatedClient();

        for ($i = 0; $i < count($data); ++$i) {
            $client->request('POST', '/api/nodes?webspace=sulu_io&language=en', $data[$i]);
            $data[$i] = (array) json_decode($client->getResponse()->getContent(), true);
        }

        return $data;
    }

    /**
     * @return ContentMapperInterface
     */
    private function getMapper()
    {
        return $this->getContainer()->get('sulu.content.mapper');
    }
}
