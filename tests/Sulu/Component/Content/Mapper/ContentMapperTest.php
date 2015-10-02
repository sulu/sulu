<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Mapper;

use Jackalope\Session;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\ContentTypeManager;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\AbstractExtension;
use Sulu\Component\Content\Extension\ExtensionInterface;
use Sulu\Component\Content\Extension\ExtensionManager;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManager;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * tests content mapper with tree strategy and phpcr mapper.
 */
class ContentMapperTest extends SuluTestCase
{
    /**
     * @var ExtensionInterface[]
     */
    private $extensions = [];

    /**
     * @var string
     */
    private $languageNamespace = 'i18n';

    /**
     * @var ContentMapper
     */
    private $mapper;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * @var ExtensionManager
     */
    private $extensionManager;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var ContentTypeManager
     */
    private $contentTypeManager;

    public function setUp()
    {
        $this->initPhpcr();
        $this->extensions = [new TestExtension('test1'), new TestExtension('test2', 'test2')];
        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->contentTypeManager = $this->getContainer()->get('sulu.content.type_manager');

        $this->tokenStorage = $this->getContainer()->get('security.token_storage');

        $token = $this->createUserTokenWithId(1);
        $this->tokenStorage->setToken($token);

        foreach ($this->extensions as $extension) {
            $this->extensionManager->addExtension($extension);
        }
    }

    public function testSave()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        $result = $this->mapper->saveRequest(
            ContentMapperRequest::create()
                ->setWebspaceKey('sulu_io')
                ->setTemplateKey('overview')
                ->setLocale('de')
                ->setUserId(1)
                ->setData($data)
        );

        $this->assertEquals('Testname', $result->getPropertyValue('title'));
        $this->assertEquals(
            [
                'tag1',
                'tag2',
            ],
            $result->getPropertyValue('tags')
        );
        $this->assertEquals('/news/test', $result->getPropertyValue('url'));
        $this->assertEquals('sulu_io', $result->getPropertyValue('article'));
        $this->assertEmpty($result->getNavContexts());

        $route = $this->documentManager->find('/cmf/sulu_io/routes/de/news/test', 'de');
        $page = $route->getTargetDocument();

        $this->assertNotNull($page);
        $this->assertEquals('Testname', $page->getTitle());
        $this->assertEquals('sulu_io', $page->getStructure()->getProperty('article')->getValue());
        $this->assertEquals(['tag1', 'tag2'], $page->getStructure()->getProperty('tags')->getValue());
        $this->assertEquals('overview', $page->getStructureType());
        $this->assertEquals(
            WorkflowStage::TEST,
            $page->getWorkflowStage()
        );

        // no navigationContext saved
        $this->assertEquals(false, $page->getStructure()->hasProperty('navContexts'));
    }

    public function provideSaveShadow()
    {
        return [
            [
                [
                    'is_shadow' => false,
                    'language' => 'de',
                    'shadow_base_language' => null,
                ],
                [
                    'is_shadow' => true,
                    'language' => 'en',
                    'shadow_base_language' => 'de_at',
                ],
                [
                    'exception' => 'Attempting to create shadow for "en" on a non-concrete locale "de_at" for document at "/cmf/sulu_io/contents/testname". Concrete languages are "de"',
                ],
            ],
            [
                [
                    'is_shadow' => false,
                    'language' => 'de',
                    'shadow_base_language' => 'fr',
                ],
                null,
                [],
            ],
            [
                [
                    'is_shadow' => true,
                    'language' => 'de',
                    'shadow_base_language' => 'de',
                ],
                null,
                [
                    'exception' => 'shadow of itself',
                ],
            ],
            [
                [
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'de',
                ],
                [
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at',
                ],
                [],
            ],
            [
                [
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'en_us',
                ],
                [
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at',
                ],
                [],
            ],
            [
                [
                    'is_shadow' => false,
                    'language' => 'de_at',
                    'shadow_base_language' => 'en_us',
                ],
                [
                    'is_shadow' => true,
                    'language' => 'en_us',
                    'shadow_base_language' => 'de_at',
                    'url' => null,
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider provideSaveShadow
     */
    public function testSaveShadow(
        $node1,
        $node2,
        $expectations
    ) {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        if (isset($expectations['exception'])) {
            $this->setExpectedException('\RuntimeException', $expectations['exception']);
        }

        $nodes = [$node1];
        if ($node2) {
            $nodes[] = $node2;
        }

        $structures = [];
        foreach ($nodes as $i => $node) {
            if (array_key_exists('url', $node)) {
                $data['url'] = $node['url'];
            }

            // NOTE: Each structure here is a new instance, however the document within
            //       is the same (its the same node). We need to cast to array to get a snapshot
            //       of the structure.
            $structures[$i] = $this->mapper->save(
                $data,
                'overview',
                'sulu_io',
                $node['language'],
                1,
                true,
                isset($structures[0]) ? $structures[0]['id'] : null,
                null,
                null,
                $node['is_shadow'],
                $node['shadow_base_language']
            )->toArray();
        }

        $this->assertFalse($structures[0]['shadowOn']);

        if (isset($structures[1]) && $nodes[1]['is_shadow']) {
            $this->assertTrue($structures[1]['shadowOn']);

            $node = $this->session->getNode('/cmf/sulu_io/routes/' . $node['language'] . '/news/test');
        }
    }

    public function testLoad()
    {
        $data = ContentMapperRequest::create('page')
            ->setLocale('de')
            ->setTemplateKey('overview')
            ->setData(
                [
                    'title' => 'Testname',
                    'tags' => [
                        'tag1',
                        'tag2',
                    ],
                    'url' => '/news/test',
                    'article' => 'sulu_io',
                ]
            )
            ->setWebspaceKey('sulu_io')
            ->setUserId(1);

        $structure = $this->mapper->saveRequest($data);

        $content = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');

        $this->assertNotNull($content->getUuid());
        $this->assertEquals('/testname', $content->getPath());
        $this->assertEquals('sulu_io', $content->getWebspaceKey());
        $this->assertEquals('de', $content->getLanguageCode());
        $this->assertEquals('overview', $content->getKey());
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('sulu_io', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['tag1', 'tag2'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEmpty($content->getNavContexts());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());
    }

    public function testLoadWithSmartContent()
    {
        $startPage = $this->mapper->loadStartPage('sulu_io', 'de');

        $data = ContentMapperRequest::create('page')
            ->setLocale('de')
            ->setTemplateKey('overview_smart_content')
            ->setData(
                [
                    'title' => 'Testname',
                    'tags' => [
                        'tag1',
                        'tag2',
                    ],
                    'url' => '/news',
                    'article' => 'sulu_io',
                    'smartcontent' => [
                        'dataSource' => $startPage->getUuid(),
                    ],
                ]
            )
            ->setWebspaceKey('sulu_io')
            ->setState(Structure::STATE_PUBLISHED)
            ->setUserId(1);

        $structure = $this->mapper->saveRequest($data);

        $childData = ContentMapperRequest::create('page')
            ->setLocale('de')
            ->setTemplateKey('default')
            ->setData(
                [
                    'title' => 'Testname',
                    'url' => '/news/child',
                    'article' => 'sulu_io',
                ]
            )
            ->setWebspaceKey('sulu_io')
            ->setState(Structure::STATE_PUBLISHED)
            ->setUserId(1);

        $childStructure = $this->mapper->saveRequest($childData);

        $content = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');

        $smartContentType = $this->contentTypeManager->get('smart_content');
        $smartContentData = $smartContentType->getContentData($content->getProperty('smartcontent'));

        $this->assertInstanceOf('DateTime', $smartContentData[0]['published']);
    }

    public function testNewProperty()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        $contentBefore = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');
        /** @var NodeInterface $contentNode */
        $contentNode = $route->getPropertyValue('sulu:content');
        // simulate new property article, by deleting the property
        /** @var PropertyInterface $articleProperty */
        $articleProperty = $contentNode->getProperty($this->languageNamespace . ':de-article');
        $this->session->removeItem($articleProperty->getPath());
        $this->session->save();
        $this->documentManager->clear();

        /** @var StructureInterface $content */
        $content = $this->mapper->load($contentBefore->getUuid(), 'sulu_io', 'de');

        // test values
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['tag1', 'tag2'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());
    }

    public function testLoadByRL()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('sulu_io', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['tag1', 'tag2'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());
    }

    public function testUpdate()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        $data['tags'][0] = 'thats cool';
        $data['article'] = 'thats a new test';

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('thats a new test', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['thats cool', 'tag2', 'tag3'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(
            'thats a new test',
            $content->getProperty($this->languageNamespace . ':de-article')->getString()
        );
        $this->assertEquals(
            ['thats cool', 'tag2', 'tag3'],
            $content->getPropertyValue($this->languageNamespace . ':de-tags')
        );
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testPartialUpdate()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('sulu_io', $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['tag2', 'tag3'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('sulu_io', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(['tag2', 'tag3'], $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testNonPartialUpdate()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['tags'][] = 'tag3';
        unset($data['tags'][0]);
        unset($data['article']);

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(['tag2', 'tag3'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-article'));
        $this->assertEquals(['tag2', 'tag3'], $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateNullValue()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['tags'] = null;
        $data['article'] = null;

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, false, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals(null, $content->article);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(null, $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-article'));
        $this->assertEquals(false, $content->hasProperty($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateTemplate()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data = [
            'title' => 'Testname',
            'blog' => 'this is a blog test',
        ];

        // update content
        $this->mapper->save($data, 'default', 'sulu_io', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'de');

        // old properties not exists in structure
        $this->assertEquals(false, $content->hasProperty('article'));
        $this->assertEquals(false, $content->hasProperty('tags'));

        // old properties are right
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('/news/test', $content->url);
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // new property is set
        $this->assertEquals('this is a blog test', $content->blog);

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');
        $content = $route->getPropertyValue('sulu:content');

        // old properties exists in node
        $this->assertEquals('sulu_io', $content->getPropertyValue($this->languageNamespace . ':de-article'));
        $this->assertEquals(['tag1', 'tag2'], $content->getPropertyValue($this->languageNamespace . ':de-tags'));

        // property of new structure exists
        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('this is a blog test', $content->getPropertyValue('blog'));
        $this->assertEquals('default', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));
    }

    public function testUpdateURL()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test/test', 'sulu_io', 'de');

        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('sulu_io', $content->article);
        $this->assertEquals('/news/test/test/test', $content->url);
        $this->assertEquals(['tag1', 'tag2'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('sulu_io', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(['tag1', 'tag2'], $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));

        // old resource locator is not a route (has property sulu:content), it is a history (has property sulu:route)
        $oldRoute = $root->getNode('cmf/sulu_io/routes/de/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testNameUpdate()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1);

        // change simple content
        $data['title'] = 'test';

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1, true, $structure->getUuid());

        // TODO works after this issue is fixed? but its not necessary
//        // check read
//        $content = $this->mapper->loadByResourceLocator('/news/test', 'sulu_io', 'en');
//
//        $this->assertEquals('sulu_io', $content->title);
//        $this->assertEquals('sulu_io', $content->article);
//        $this->assertEquals('/news/test', $content->url);
//        $this->assertEquals(array('tag1', 'tag2'), $content->tags);
//        $this->assertEquals(1, $content->getCreator());
//        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $content = $root->getNode('cmf/sulu_io/contents/test');

        $this->assertEquals('test', $content->getProperty($this->languageNamespace . ':en-title')->getString());
        $this->assertEquals('sulu_io', $content->getProperty($this->languageNamespace . ':en-article')->getString());
        $this->assertEquals(['tag1', 'tag2'], $content->getPropertyValue($this->languageNamespace . ':en-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':en-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':en-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':en-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':en-changer'));
    }

    public function testUpdateUrlTwice()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];

        // save content
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        // change simple content
        $data['url'] = '/news/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, null, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/test/test', 'sulu_io', 'de');
        $this->assertEquals('Testname', $content->title);

        // change simple content
        $data['url'] = '/news/asdf/test/test';

        // update content
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $structure->getUuid());

        // check read
        $content = $this->mapper->loadByResourceLocator('/news/asdf/test/test', 'sulu_io', 'de');
        $this->assertEquals('Testname', $content->title);
        $this->assertEquals('sulu_io', $content->article);
        $this->assertEquals('/news/asdf/test/test', $content->url);
        $this->assertEquals(['tag1', 'tag2'], $content->tags);
        $this->assertEquals(StructureInterface::STATE_TEST, $content->getNodeState());
        $this->assertEquals(1, $content->getCreator());
        $this->assertEquals(1, $content->getChanger());

        // check repository
        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/asdf/test/test');

        $content = $route->getPropertyValue('sulu:content');

        $this->assertEquals('Testname', $content->getProperty($this->languageNamespace . ':de-title')->getString());
        $this->assertEquals('sulu_io', $content->getProperty($this->languageNamespace . ':de-article')->getString());
        $this->assertEquals(['tag1', 'tag2'], $content->getPropertyValue($this->languageNamespace . ':de-tags'));
        $this->assertEquals('overview', $content->getPropertyValue($this->languageNamespace . ':de-template'));
        $this->assertEquals(
            StructureInterface::STATE_TEST,
            $content->getPropertyValue($this->languageNamespace . ':de-state')
        );
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-creator'));
        $this->assertEquals(1, $content->getPropertyValue($this->languageNamespace . ':de-changer'));

        $oldRoute = $root->getNode('cmf/sulu_io/routes/de/news/test');
        $this->assertTrue($oldRoute->hasProperty('sulu:content'));
        $this->assertTrue($oldRoute->hasProperty('sulu:history'));
        $this->assertTrue($oldRoute->getPropertyValue('sulu:history'));

        // history should reference to new route
        $history = $oldRoute->getPropertyValue('sulu:content');
        $this->assertEquals($route->getIdentifier(), $history->getIdentifier());
    }

    public function testContentTree()
    {
        $data = [
            [
                'title' => 'News',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news',
                'article' => 'asdfasdfasdf',
            ],
            [
                'title' => 'Testnews-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-1',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2/test-1',
                'article' => 'sulu_io',
            ],
        ];

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'sulu_io', 'de', 1, true, null, $root->getUuid());
        $this->mapper->save($data[3], 'overview', 'sulu_io', 'de', 1, true, null, $child->getUuid());

        // check nodes
        $content = $this->mapper->loadByResourceLocator('/news', 'sulu_io', 'de');
        $this->assertEquals('News', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-1', 'sulu_io', 'de');
        $this->assertEquals('Testnews-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2', 'sulu_io', 'de');
        $this->assertEquals('Testnews-2', $content->title);
        $this->assertTrue($content->getHasChildren());

        $content = $this->mapper->loadByResourceLocator('/news/test-2/test-1', 'sulu_io', 'de');
        $this->assertEquals('Testnews-2-1', $content->title);
        $this->assertFalse($content->getHasChildren());

        // check content repository
        $root = $this->session->getRootNode();
        $contentRootNode = $root->getNode('cmf/sulu_io/contents');

        $newsNode = $contentRootNode->getNode('news');
        $this->assertEquals(2, count($newsNode->getNodes()));
        $this->assertEquals('News', $newsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $testNewsNode = $newsNode->getNode('testnews-1');
        $this->assertEquals('Testnews-1', $testNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $testNewsNode = $newsNode->getNode('testnews-2');
        $this->assertEquals(1, count($testNewsNode->getNodes()));
        $this->assertEquals('Testnews-2', $testNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));

        $subTestNewsNode = $testNewsNode->getNode('testnews-2-1');
        $this->assertEquals('Testnews-2-1', $subTestNewsNode->getPropertyValue($this->languageNamespace . ':de-title'));
    }

    private function prepareTreeTestData()
    {
        $data = [
            [
                'title' => 'News',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news',
                'article' => 'asdfasdfasdf',
            ],
            [
                'title' => 'Testnews-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-1',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2/test-1',
                'article' => 'sulu_io',
            ],
        ];

        $this->saveStartPage(
            ['title' => 'Start Page'],
            'overview',
            'sulu_io',
            'de',
            1
        );

        // save root content
        $result['root'] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $result['root']->getUuid());
        $result['child'] = $this->mapper->save(
            $data[2],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            null,
            $result['root']->getUuid()
        );
        $result['subchild'] = $this->mapper->save(
            $data[3],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            null,
            $result['child']->getUuid()
        );

        return $result;
    }

    public function testLoadByParent()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        // get root children
        $children = $this->mapper->loadByParent(null, 'sulu_io', 'de');
        $this->assertEquals(1, count($children));

        $this->assertEquals('News', $children[0]->title);

        // get children from 'News'
        $rootChildren = $this->mapper->loadByParent($root->getUuid(), 'sulu_io', 'de');
        $this->assertEquals(2, count($rootChildren));

        $this->assertEquals('Testnews-1', $rootChildren[0]->title);
        $this->assertEquals('Testnews-2', $rootChildren[1]->title);

        $testNewsChildren = $this->mapper->loadByParent($child->getUuid(), 'sulu_io', 'de');
        $this->assertEquals(1, count($testNewsChildren));

        $this->assertEquals('Testnews-2-1', $testNewsChildren[0]->title);

        $nodes = $this->mapper->loadByParent($root->getUuid(), 'sulu_io', 'de', null);
        $this->assertEquals(3, count($nodes));
    }

    public function testLoadByParentFlat()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'sulu_io', 'de', 2, true);
        $this->assertEquals(3, count($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);

        $children = $this->mapper->loadByParent(null, 'sulu_io', 'de', 3, true);
        $this->assertEquals(4, count($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('Testnews-1', $children[1]->title);
        $this->assertEquals('Testnews-2', $children[2]->title);
        $this->assertEquals('Testnews-2-1', $children[3]->title);

        $children = $this->mapper->loadByParent($child->getUuid(), 'sulu_io', 'de', 3, true);
        $this->assertEquals(1, count($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
    }

    public function testLoadByParentTree()
    {
        $data = $this->prepareTreeTestData();
        /** @var StructureInterface $root */
        $root = $data['root'];
        /** @var StructureInterface $child */
        $child = $data['child'];

        $children = $this->mapper->loadByParent(null, 'sulu_io', 'de', 2, false);
        // /News
        $this->assertEquals(1, count($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('/news', $children[0]->getPath());

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, count($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);
        $this->assertEquals('/news/testnews-1', $tmp->getPath());

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertCount(1, $tmp->getChildren()); // children loaded lazily now
        $this->assertTrue($tmp->getHasChildren());
        $this->assertEquals('Testnews-2', $tmp->title);
        $this->assertEquals('/news/testnews-2', $tmp->getPath());

        $children = $this->mapper->loadByParent(null, 'sulu_io', 'de', 3, false);
        // /News
        $this->assertEquals(1, count($children));
        $this->assertEquals('News', $children[0]->title);
        $this->assertEquals('/news', $children[0]->getPath());

        // /News/Testnews-1
        $tmp = $children[0]->getChildren()[0];
        $this->assertEquals(0, count($tmp->getChildren()));
        $this->assertEquals('Testnews-1', $tmp->title);
        $this->assertEquals('/news/testnews-1', $tmp->getPath());

        // /News/Testnews-2
        $tmp = $children[0]->getChildren()[1];
        $this->assertEquals(1, count($tmp->getChildren()));
        $this->assertEquals('Testnews-2', $tmp->title);
        $this->assertEquals('/news/testnews-2', $tmp->getPath());

        // /News/Testnews-2/Testnews-2-1
        $tmp = $children[0]->getChildren()[1]->getChildren()[0];
        $this->assertCount(0, $tmp->getChildren()); // children loaded lazily
        $this->assertFalse($tmp->getHasChildren());
        $this->assertEquals('Testnews-2-1', $tmp->title);
        $this->assertEquals('/news/testnews-2/testnews-2-1', $tmp->getPath());

        $children = $this->mapper->loadByParent($child->getUuid(), 'sulu_io', 'de', 3, false);
        $this->assertEquals(1, count($children));
        $this->assertEquals('Testnews-2-1', $children[0]->title);
        $this->assertEquals('/news/testnews-2/testnews-2-1', $children[0]->getPath());
    }

    public function testDelete()
    {
        $data = [
            [
                'title' => 'News',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news',
                'article' => 'asdfasdfasdf',
            ],
            [
                'title' => 'Testnews-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-1',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2',
                'article' => 'sulu_io',
            ],
            [
                'title' => 'Testnews-2-1',
                'tags' => [
                    'tag1',
                    'tag2',
                ],
                'url' => '/news/test-2/test-1',
                'article' => 'sulu_io',
            ],
        ];

        // save root content
        $root = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);

        // add a child content
        $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $root->getUuid());
        $child = $this->mapper->save($data[2], 'overview', 'sulu_io', 'de', 1, true, null, $root->getUuid());
        $subChild = $this->mapper->save($data[3], 'overview', 'sulu_io', 'de', 1, true, null, $child->getUuid());

        // delete /news/test-2/test-1
        $this->mapper->delete($child->getUuid(), 'sulu_io');

        // check
        try {
            $this->mapper->load($child->getUuid(), 'sulu_io', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (DocumentNotFoundException $ex) {
        }

        try {
            $this->mapper->load($subChild->getUuid(), 'sulu_io', 'de');
            $this->assertTrue(false, 'Node should not exists');
        } catch (DocumentNotFoundException $ex) {
        }

        $result = $this->mapper->loadByParent($root->getUuid(), 'sulu_io', 'de');
        $this->assertEquals(1, count($result));
    }

    public function testCleanUp()
    {
        $data = [
            'title' => 'ä   ü ö   Ä Ü Ö',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/',
            'article' => 'article',
        ];

        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1);

        $node = $this->session->getNodeByIdentifier($structure->getUuid());

        $this->assertEquals($node->getName(), 'ae-ue-oe-ae-ue-oe');
        $this->assertEquals($node->getPath(), '/cmf/sulu_io/contents/ae-ue-oe-ae-ue-oe');
    }

    public function testStateTransition()
    {
        // default state TEST
        $data1 = [
            'title' => 't1',
            'url' => '/url',
        ];
        $data1 = $this->mapper->save($data1, 'overview', 'sulu_io', 'de', 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data1->getNodeState());
        $this->assertNull($data1->getPublished());
        $this->assertFalse($data1->getPublishedState());

        // save with state PUBLISHED
        $data2 = [
            'url' => '/url1',
            'title' => 't2',
        ];
        $data2 = $this->mapper->save($data2, 'overview', 'sulu_io', 'de', 1, true, null, null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data2->getNodeState());
        $this->assertNotNull($data2->getPublished());
        $this->assertTrue($data2->getPublishedState());

        sleep(1);
        // change state from TEST to PUBLISHED
        $data3 = [
            'title' => 't1',
        ];
        $data3 = $this->mapper->save($data3, 'overview', 'sulu_io', 'de', 1, true, $data1->getUuid(), null, 2);
        $this->assertEquals(StructureInterface::STATE_PUBLISHED, $data3->getNodeState());
        $this->assertNotNull($data3->getPublished());
        $this->assertTrue($data3->getPublishedState());
        $this->assertTrue($data3->getPublished() > $data2->getPublished());

        // change state from PUBLISHED to TEST (exception)
        $data4 = [
            'title' => 't2',
        ];
        $data4 = $this->mapper->save($data4, 'overview', 'sulu_io', 'de', 1, true, $data2->getUuid(), null, 1);
        $this->assertEquals(StructureInterface::STATE_TEST, $data4->getNodeState());
        $this->assertNull($data4->getPublished());
        $this->assertFalse($data4->getPublishedState());
    }

    public function testNavigationContext()
    {
        $navContexts = ['main', 'footer'];
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
            'navContexts' => $navContexts,
        ];

        $result = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);
        $content = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');
        $node = $route->getPropertyValue('sulu:content');

        $this->assertEquals($navContexts, $node->getPropertyValue($this->languageNamespace . ':de-navContexts'));
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save(
            $data,
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            $result->getUuid(),
            null,
            null,
            false
        );
        $content = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $result->getUuid());
        $content = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());

        $result = $this->mapper->save(
            $data,
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            $result->getUuid()
        );
        $content = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');
        $this->assertEquals($navContexts, $result->getNavContexts());
        $this->assertEquals($navContexts, $content->getNavContexts());
    }

    public function testLoadBySql2()
    {
        $this->prepareTreeTestData();

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'sulu_io');

        $this->assertEquals(5, count($result));

        $result = $this->mapper->loadBySql2('SELECT * FROM [sulu:content]', 'de', 'sulu_io', 2);

        $this->assertEquals(2, count($result));
    }

    public function testSameName()
    {
        $data = [
            'title' => 'Test',
            'tags' => ['tag1'],
            'url' => '/test-1',
            'article' => 'sulu_io',
        ];

        $d1 = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);
        $data['url'] = '/test-2';
        $data['tags'] = ['tag2'];
        $d2 = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(['tag1'], $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(['tag2'], $d2->tags);

        $this->assertNotNull($this->session->getNode('/cmf/sulu_io/contents/test'));
        $this->assertNotNull($this->session->getNode('/cmf/sulu_io/contents/test-1'));

        $d1 = $this->mapper->load($d1->getUuid(), 'sulu_io', 'de');
        $d2 = $this->mapper->load($d2->getUuid(), 'sulu_io', 'de');

        $this->assertEquals('Test', $d1->title);
        $this->assertEquals(['tag1'], $d1->tags);
        $this->assertEquals('Test', $d2->title);
        $this->assertEquals(['tag2'], $d2->tags);
    }

    public function testBreadcrumb()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareTreeTestData();

        /** @var BreadcrumbItemInterface[] $result */
        $result = $this->mapper->loadBreadcrumb($data['subchild']->getUuid(), 'de', 'sulu_io');

        $this->assertEquals(3, count($result));
        $this->assertEquals(0, $result[0]->getDepth());
        $this->assertEquals('Start Page', $result[0]->getTitle());

        $this->assertEquals(1, $result[1]->getDepth());
        $this->assertEquals('News', $result[1]->getTitle());
        $this->assertEquals($data['root']->getUuid(), $result[1]->getUuid());

        $this->assertEquals(2, $result[2]->getDepth());
        $this->assertEquals('Testnews-2', $result[2]->getTitle());
        $this->assertEquals($data['child']->getUuid(), $result[2]->getUuid());
    }

    private function prepareGhostTestData()
    {
        $data = [
            [
                'title' => 'News-EN',
                'url' => '/news',
            ],
            [
                'title' => 'News-DE_AT',
                'url' => '/news',
            ],
            [
                'title' => 'Products-EN',
                'url' => '/products',
            ],
            [
                'title' => 'Products-DE',
                'url' => '/products',
            ],
            [
                'title' => 'Team-DE',
                'url' => '/team-de',
            ],
        ];

        $this->saveStartPage(
            ['title' => 'Start Page'],
            'overview',
            'sulu_io',
            'de',
            1
        );

        // save root content
        $result['news-en'] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'en', 1);
        $result['news-de_at'] = $this->mapper->save(
            $data[1],
            'overview',
            'sulu_io',
            'de_at',
            1,
            true,
            $result['news-en']->getUuid()
        );

        $result['products-en'] = $this->mapper->save(
            $data[2],
            'overview',
            'sulu_io',
            'en',
            1,
            true
        );

        $result['products-de'] = $this->mapper->save(
            $data[3],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            $result['products-en']->getUuid()
        );

        $result['team-de'] = $this->mapper->save(
            $data[4],
            'overview',
            'sulu_io',
            'de',
            1,
            true
        );

        return $result;
    }

    public function testGhost()
    {
        /** @var StructureInterface[] $data */
        $data = $this->prepareGhostTestData();

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'en', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('en', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages exists in en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'en', 1, true, false, true);
        $this->assertEquals(2, count($result));
        $this->assertEquals('en', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('en', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());

        // both pages are ghosts in en_us from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'en_us', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('en_us', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('en_us', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('en_us', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'en_us', 1, true, false, true);
        $this->assertEquals(0, count($result));

        // one page not exists in de (ghost from de_at), other exists in de
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'de', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertNull($result[2]->getType());

        // one page exists in de (without ghosts)
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'de', 1, true, false, true);
        $this->assertEquals(2, count($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'de', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('de', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('de_at', $result[0]->getType()->getValue());
        $this->assertEquals('de', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertNull($result[1]->getType());
        $this->assertEquals('de', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertNull($result[2]->getType());

        // one page not exists in de_at (ghost from de), other exists in de_at
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'de_at', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('de_at', $result[0]->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result[0]->getPropertyValue('title'));
        $this->assertNull($result[0]->getType());
        $this->assertEquals('de_at', $result[1]->getLanguageCode());
        $this->assertEquals('Products-DE', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('de', $result[1]->getType()->getValue());
        $this->assertEquals('de_at', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // both pages are ghosts in es from en
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'es', 1, true, false, false);
        $this->assertEquals(3, count($result));
        $this->assertEquals('es', $result[0]->getLanguageCode());
        $this->assertEquals('News-EN', $result[0]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[0]->getType()->getName());
        $this->assertEquals('en', $result[0]->getType()->getValue());
        $this->assertEquals('es', $result[1]->getLanguageCode());
        $this->assertEquals('Products-EN', $result[1]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[1]->getType()->getName());
        $this->assertEquals('en', $result[1]->getType()->getValue());
        $this->assertEquals('es', $result[2]->getLanguageCode());
        $this->assertEquals('Team-DE', $result[2]->getPropertyValue('title'));
        $this->assertEquals('ghost', $result[2]->getType()->getName());
        $this->assertEquals('de', $result[2]->getType()->getValue());

        // no page exists in en_us without ghosts
        /** @var StructureInterface[] $result */
        $result = $this->mapper->loadByParent(null, 'sulu_io', 'es', 1, true, false, true);
        $this->assertEquals(0, count($result));

        // load content as de -> no ghost content
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'sulu_io', 'de', false);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('', $result->getPropertyValue('title'));
        $this->assertNull($result->getType());

        // load content as de -> load ghost content
        $this->documentManager->clear();
        $result = $this->mapper->load($data['news-de_at']->getUuid(), 'sulu_io', 'de', true);
        $this->assertEquals('de', $result->getLanguageCode());
        $this->assertEquals('News-DE_AT', $result->getPropertyValue('title'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de_at', $result->getType()->getValue());

        $this->documentManager->clear();
        // load only in german available page in english
        $result = $this->mapper->load($data['team-de']->getUuid(), 'sulu_io', 'en', true);
        $this->assertEquals('en', $result->getLanguageCode());
        $this->assertEquals('Team-DE', $result->getPropertyValue('title'));
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de', $result->getType()->getValue());
    }

    public function prepareLoadShadowData()
    {
        $data = [
            [
                'title' => 'hello',
                'article' => 'German',
                'shadow' => false,
                'language' => 'de',
                'is_shadow' => false,
                'shadow_base_language' => null,
            ],
            [
                'title' => 'hello',
                'article' => 'Austrian',
                'shadow' => true,
                'language' => 'de_at',
                'is_shadow' => true,
                'shadow_base_language' => 'de',
            ],
            [
                'title' => 'random',
                'article' => 'Auslander',
                'shadow' => true,
                'language' => 'de_at',
                'is_shadow' => false,
                'shadow_base_language' => 'de',
            ],
        ];

        $result = [];
        foreach ($data as $dataItem) {
            $result[$dataItem['title']][$dataItem['language']] = $this->mapper->save(
                [
                    'title' => $dataItem['title'],
                    'url' => '/' . $dataItem['title'],
                    'article' => $dataItem['article'],
                ],
                'overview',
                'sulu_io',
                $dataItem['language'],
                1,
                true,
                isset($result[$dataItem['title']]['de']) ? $result[$dataItem['title']]['de']->getUuid() : null,
                null,
                null,
                $dataItem['is_shadow'],
                $dataItem['shadow_base_language']
            );
        }

        return $result;
    }

    public function testLoadShadow()
    {
        $result = $this->prepareLoadShadowData();

        $uuid = $result['hello']['de']->getUuid();

        $structure = $this->mapper->load($uuid, 'sulu_io', 'de');
        $this->assertFalse($structure->getIsShadow());
        $this->assertEquals('German', $structure->getProperty('article')->getValue());

        $structure = $this->mapper->load($uuid, 'sulu_io', 'de_at', false);
        $this->assertTrue($structure->getIsShadow());
        $this->assertEquals('de', $structure->getShadowBaseLanguage());
        $this->assertEquals('de_at', $structure->getLanguageCode());

        // this is a shadow, so it should be "German" not "Austrian"
        $this->assertEquals('German', $structure->getProperty('article')->getValue());
        $this->assertEquals(['de' => 'de_at'], $structure->getEnabledShadowLanguages());

        // the node has only one concrete language
        $this->assertEquals(['de'], $structure->getConcreteLanguages());
    }

    public function testTranslatedResourceLocator()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test',
            'article' => 'sulu_io',
        ];
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1);
        $content = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('/news/test', $content->url);

        $contentDE = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('', $contentDE->url);

        $nodeEN = $this->session->getNode('/cmf/sulu_io/routes/en/news/test');
        $this->assertNotNull($nodeEN);
        $this->assertFalse($nodeEN->getPropertyValue('sulu:history'));
        $this->assertFalse($this->session->getNode('/cmf/sulu_io/routes/de')->hasNode('news/test'));
        $this->assertNotNull($this->session->getNode('/cmf/sulu_io/routes/en/news/test'));

        $data = [
            'title' => 'Testname',
            'url' => '/neuigkeiten/test',
        ];
        $structure = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1, true, $structure->getUuid());
        $content = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/neuigkeiten/test', $content->url);

        $nodeDE = $this->session->getNode('/cmf/sulu_io/routes/de/neuigkeiten/test');
        $this->assertNotNull($nodeDE);
        $this->assertFalse($nodeDE->getPropertyValue('sulu:history'));

        $contentEN = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/news/test', $contentEN->url);

        $this->assertTrue($this->session->getNode('/cmf/sulu_io/routes/de')->hasNode('neuigkeiten/test'));
        $this->assertFalse($this->session->getNode('/cmf/sulu_io/routes/de')->hasNode('news/test'));
        $this->assertFalse($this->session->getNode('/cmf/sulu_io/routes/en')->hasNode('neuigkeiten/test'));
        $this->assertTrue($this->session->getNode('/cmf/sulu_io/routes/en')->hasNode('news/test'));
        $this->assertNotNull($this->session->getNode('/cmf/sulu_io/routes/de/neuigkeiten/test'));
    }

    public function testBlock()
    {
        $data = [
            'title' => 'Test-name',
            'url' => '/test',
            'block1' => [
                [
                    'type' => 'default',
                    'title' => 'Block-name-1',
                    'article' => 'Block-Article-1',
                ],
                [
                    'type' => 'default',
                    'title' => 'Block-name-2',
                    'article' => 'Block-Article-2',
                ],
            ],
        ];

        // check save
        $structure = $this->mapper->save($data, 'complex', 'sulu_io', 'de', 1);
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            [
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1'],
            ]
        );

        // change sorting
        $tmp = $data['block1'][0];
        $data['block1'][0] = $data['block1'][1];
        $data['block1'][1] = $tmp;
        $structure = $this->mapper->save($data, 'complex', 'sulu_io', 'de', 1, true, $structure->getUuid());
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            [
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1'],
            ]
        );

        // check load
        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');
        $result = $structure->toArray();
        $this->assertEquals(
            $data,
            [
                'title' => $result['title'],
                'url' => $result['url'],
                'block1' => $result['block1'],
            ]
        );
    }

    public function testMultilingual()
    {
        // change simple content
        $dataDe = [
            'title' => 'Testname-DE',
            'blog' => 'German',
            'url' => '/news/test',
        ];

        // update content
        $structureDe = $this->mapper->save($dataDe, 'default', 'sulu_io', 'de', 1);

        $dataEn = [
            'title' => 'Testname-EN',
            'blog' => 'English',
            'url' => '/news/test',
        ];
        $structureEn = $this->mapper->save($dataEn, 'default', 'sulu_io', 'en', 1, true, $structureDe->getUuid())->toArray();
        $structureDe = $this->mapper->load($structureDe->getUuid(), 'sulu_io', 'de');

        // check data
        $this->assertNotEquals($structureDe->getPropertyValue('title'), $structureEn['title']);
        $this->assertEquals($structureDe->getPropertyValue('blog'), $structureEn['blog']);

        $this->assertEquals($dataEn['title'], $structureEn['title']);
        $this->assertEquals($dataEn['blog'], $structureEn['blog']);

        $this->assertEquals($dataDe['title'], $structureDe->getPropertyValue('title'));
        // En has overritten german content
        $this->assertEquals($dataEn['blog'], $structureDe->getPropertyValue('blog'));

        $root = $this->session->getRootNode();
        $route = $root->getNode('cmf/sulu_io/routes/de/news/test');
        /** @var NodeInterface $content */
        $content = $route->getPropertyValue('sulu:content');
        $this->assertEquals($dataDe['title'], $content->getPropertyValue($this->languageNamespace . ':de-title'));
        $this->assertNotEquals($dataDe['blog'], $content->getPropertyValue('blog'));
        $this->assertEquals($dataEn['title'], $content->getPropertyValue($this->languageNamespace . ':en-title'));
        $this->assertEquals($dataEn['blog'], $content->getPropertyValue('blog'));

        $this->assertFalse($content->hasProperty($this->languageNamespace . ':de-blog'));
        $this->assertFalse($content->hasProperty($this->languageNamespace . ':en-blog'));
        $this->assertFalse($content->hasProperty('title'));
    }

    public function testMandatory()
    {
        $data = [
            'title' => 'Testname',
            'url' => '/news/test',
        ];

        $this->setExpectedException(
            '\Sulu\Component\Content\Exception\MandatoryPropertyException',
            'Property "mandatory" in structure "mandatory" is required but no value was given.'
        );

        $this->mapper->save($data, 'mandatory', 'sulu_io', 'de', 1);
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareBigTreeTestData()
    {
        $data = [
            [
                'data' => [
                    'title' => 'Products',
                    'url' => '/products',
                ],
                'children' => [
                    [
                        'data' => [
                            'title' => 'Products1',
                            'url' => '/products/products-1',
                        ],
                        'children' => [],
                    ],
                ],
            ],
            [
                'data' => [
                    'title' => 'News',
                    'url' => '/news',
                ],
                'children' => [
                    [
                        'data' => [
                            'title' => 'News-1',
                            'url' => '/news/news-1',
                        ],
                        'children' => [
                            [
                                'data' => [
                                    'title' => 'SubNews-1',
                                    'url' => '/news/news-1/subnews-1',
                                ],
                                'children' => [],
                            ],
                            [
                                'data' => [
                                    'title' => 'SubNews-2',
                                    'url' => '/news/news-1/subnews-2',
                                ],
                                'children' => [],
                            ],
                            [
                                'data' => [
                                    'title' => 'SubNews-3',
                                    'url' => '/news/news-1/subnews-3',
                                ],
                                'children' => [
                                    [
                                        'data' => [
                                            'title' => 'SubSubNews-1',
                                            'url' => '/news/news-1/subnews-3/subsubnews-1',
                                        ],
                                        'children' => [],
                                    ],
                                    [
                                        'data' => [
                                            'title' => 'SubSubNews-2',
                                            'url' => '/news/news-1/subnews-3/subsubnews-2',
                                        ],
                                        'children' => [],
                                    ],
                                    [
                                        'data' => [
                                            'title' => 'SubSubNews-3',
                                            'url' => '/news/news-1/subnews-3/subsubnews-3',
                                        ],
                                        'children' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'data' => [
                            'title' => 'News-2',
                            'url' => '/news/news-2',
                        ],
                        'children' => [],
                    ],
                    [
                        'data' => [
                            'title' => 'News-3',
                            'url' => '/news/news-3',
                        ],
                        'children' => [],
                    ],
                ],
            ],
            [
                'data' => [
                    'title' => 'About Us',
                    'url' => '/about-us',
                ],
                'children' => [],
            ],
        ];

        return $this->saveData($data);
    }

    private function saveData($data, $uuid = null)
    {
        $result = [];
        foreach ($data as $item) {
            $itemStructure = $this->mapper->save($item['data'], 'overview', 'sulu_io', 'de', 1, true, null, $uuid);
            $this->saveData($item['children'], $itemStructure->getUuid());

            $result[] = $itemStructure;
        }

        return $result;
    }

    /**
     * It shoud load the node with the give UUID/path and all of its ancestors up to
     * and including the content root.
     */
    public function testLoadNodeAndAncestors()
    {
        $data = $this->prepareBigTreeTestData();
        $child = $data[1]->getChildren()[0]->getChildren()[2]->getChildren()[1];

        $ancestors = $this->mapper->loadNodeAndAncestors($child->getUuid(), 'de', 'sulu_io', false);

        $documentNames = [];
        foreach ($ancestors as $ancestor) {
            $documentNames[] = $ancestor->getPath();
        }

        $this->assertEquals([
            '/news/news-1/subnews-3/subsubnews-2',
            '/news/news-1/subnews-3',
            '/news/news-1',
            '/news',
            '',
        ], $documentNames);
    }

    public function testLanguageCopy()
    {
        $data = $this->prepareSinglePageTestData();

        $this->mapper->copyLanguage($data->getUuid(), 1, 'sulu_io', 'de', 'en');

        $result = $this->mapper->load($data->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);
    }

    public function testMultipleLanguagesCopy()
    {
        $data = $this->prepareSinglePageTestData();

        $this->mapper->copyLanguage($data->getUuid(), 1, 'sulu_io', 'de', ['en', 'de_at']);

        $result = $this->mapper->load($data->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);

        $result = $this->mapper->load($data->getUuid(), 'sulu_io', 'de_at');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals('/page-1', $result->url);
    }

    private function prepareCopyLanguageTree()
    {
        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'de', 1);
        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'en', 1);

        $data = [
            [
                'title' => 'test',
                'url' => '/test',
            ],
            [
                'title' => 'childtest',
                'url' => '/test/childtest',
            ],
        ];

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $data[0]->getUuid());

        return $data;
    }

    public function testCopyLanguageTree()
    {
        $data = $this->prepareCopyLanguageTree();

        $this->mapper->copyLanguage($data[0]->getUuid(), 1, 'sulu_io', 'de', 'en');
        $this->mapper->save(
            ['title' => 'test-en', 'url' => '/test-en'],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            null,
            false
        );

        $this->session->refresh(false);

        $this->mapper->copyLanguage($data[1]->getUuid(), 1, 'sulu_io', 'de', 'en');

        $result = $this->mapper->load($data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('test-en', $result->getPropertyValue('title'));
        $this->assertEquals('/test-en', $result->getPropertyValue('url'));

        $result = $this->mapper->load($data[1]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('childtest', $result->getPropertyValue('title'));
        $this->assertEquals('/test-en/childtest', $result->getPropertyValue('url'));
    }

    public function testNodeAndAncestorsExcludedGhosts()
    {
        $data = $this->prepareBigTreeTestData();
        $child = $data[1]->getChildren()[0]->getChildren()[2]->getChildren()[1];

        $result = $this->mapper->loadNodeAndAncestors($child->getUuid(), 'en', 'sulu_io', true);

        // at least homepage will be found
        $this->assertCount(1, $result);
        $this->assertEquals('/', $result[0]->getPropertyValue('url'));
    }

    public function testSection()
    {
        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];

        $structure = $this->mapper->save($data, 'section', 'sulu_io', 'en', 1);
        $resultSave = $structure->toArray();

        $this->assertEquals('/test', $resultSave['path']);
        $this->assertEquals('section', $resultSave['template']);
        $this->assertEquals('Test', $resultSave['title']);
        $this->assertEquals('Thats a good test', $resultSave['blog']);
        $this->assertEquals('/test/test', $resultSave['url']);

        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');
        $resultLoad = $structure->toArray();

        $this->assertEquals('/test', $resultLoad['path']);
        $this->assertEquals('section', $resultLoad['template']);
        $this->assertEquals('Test', $resultLoad['title']);
        $this->assertEquals('Thats a good test', $resultLoad['blog']);
        $this->assertEquals('/test/test', $resultLoad['url']);
    }

    public function testCompleteExtensions()
    {
        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
            'ext' => [
                'test1' => [
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test',
                ],
            ],
        ];

        $structure = $structure = $this->mapper->save($data, 'default', 'sulu_io', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals($data['ext']['test1'], $result['ext']['test1']);
        $this->assertEquals(
            [
                'a' => '',
                'b' => '',
            ],
            $result['ext']['test2']
        );

        $data = [
            'title' => 'Test',
            'blog' => 'Thats a good test',
            'ext' => [
                'test2' => [
                    'a' => 'a',
                    'b' => 'b',
                ],
            ],
        ];

        $structure = $this->mapper->save(
            $data,
            'default',
            'sulu_io',
            'en',
            1,
            true,
            $structure->getUuid()
        );
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $result['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'a',
                'b' => 'b',
            ],
            $result['ext']['test2']
        );

        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $result['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'a',
                'b' => 'b',
            ],
            $result['ext']['test2']
        );
    }

    public function testExtensionsLocalized()
    {
        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'localized_blog' => 'Thats a good test',
            'ext' => [
                'test1' => [
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test',
                ],
                'test2' => [
                    'a' => 'That´s a test',
                    'b' => 'That´s a second test',
                ],
            ],
        ];

        $structure = $structure = $this->mapper->save($data, 'default', 'sulu_io', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $result['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $result['ext']['test2']
        );

        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'localized_blog' => 'Das ist ein guter Test',
            'ext' => [
                'test1' => [
                    'a' => 'Das ist ein Test',
                    'b' => 'Das ist ein zweiter Test',
                ],
                'test2' => [
                    'a' => 'Das ist ein Test',
                    'b' => 'Das ist ein zweiter Test',
                ],
            ],
        ];

        $structure = $structure = $this->mapper->save(
            $data,
            'default',
            'sulu_io',
            'de',
            1,
            true,
            $structure->getUuid()
        );
        $result = $structure->toArray();

        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(
            [
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test',
            ],
            $result['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test',
            ],
            $result['ext']['test2']
        );

        $resultDE = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de')->toArray();
        $this->assertEquals('Test', $resultDE['title']);
        $this->assertEquals('Das ist ein guter Test', $resultDE['localized_blog']);
        $this->assertEquals(
            [
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test',
            ],
            $resultDE['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'Das ist ein Test',
                'b' => 'Das ist ein zweiter Test',
            ],
            $resultDE['ext']['test2']
        );

        $resultEN = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en')->toArray();
        $this->assertEquals('Test', $resultEN['title']);
        $this->assertEquals('Thats a good test', $resultEN['localized_blog']);
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $resultEN['ext']['test1']
        );
        $this->assertEquals(
            [
                'a' => 'That´s a test',
                'b' => 'That´s a second test',
            ],
            $resultEN['ext']['test2']
        );
    }

    public function testExtensions()
    {
        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];

        $structure = $this->mapper->save($data, 'default', 'sulu_io', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test1']);
        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test2']);

        $dataTest1EN = [
            'a' => 'en test1 a',
            'b' => 'en test1 b',
        ];

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest1EN, 'test1', 'sulu_io', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test2']);

        $dataTest2EN = [
            'a' => 'en test2 a',
            'b' => 'en test2 b',
        ];

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest2EN, 'test2', 'sulu_io', 'en', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals($dataTest2EN, $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'en');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Thats a good test', $result['blog']);

        $this->assertEquals($dataTest1EN, $result['ext']['test1']);
        $this->assertEquals($dataTest2EN, $result['ext']['test2']);

        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Das ist ein guter Test',
        ];

        $structure = $this->mapper->save($data, 'default', 'sulu_io', 'de', 1, true, $structure->getUuid());
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test1']);
        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test2']);

        $dataTest2DE = [
            'a' => 'de test2 a',
            'b' => 'de test2 b',
        ];

        $structure = $this->mapper->saveExtension($structure->getUuid(), $dataTest2DE, 'test2', 'sulu_io', 'de', 1);
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test1']);
        $this->assertEquals($dataTest2DE, $result['ext']['test2']);

        $structure = $this->mapper->load($structure->getUuid(), 'sulu_io', 'de');
        $result = $structure->toArray();

        $this->assertEquals('/test', $result['path']);
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertEquals('Test', $result['title']);
        $this->assertEquals('Das ist ein guter Test', $result['blog']);

        $this->assertEquals(['a' => '', 'b' => ''], $result['ext']['test1']);
        $this->assertEquals($dataTest2DE, $result['ext']['test2']);
    }

    public function testTranslatedNodeNotFound()
    {
        $data = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];

        $structure = $this->mapper->save($data, 'default', 'sulu_io', 'en', 1);
        $dataTest2DE = [
            'a' => 'de test2 a',
            'b' => 'de test2 b',
        ];

        $this->setExpectedException(
            'Sulu\Component\Content\Exception\TranslatedNodeNotFoundException',
            'Node "' . $structure->getUuid() . '" not found in localization "de"'
        );

        $this->mapper->saveExtension($structure->getUuid(), $dataTest2DE, 'test2', 'sulu_io', 'de', 1);
    }

    public function testGetRlAndName()
    {
        $data1 = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];
        $structure1 = $this->mapper->save($data1, 'default', 'sulu_io', 'en', 1);

        $data2 = [
            'title' => 'Test 1',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'internal_link' => $structure1->getUuid(),
        ];
        $structure2 = $this->mapper->save($data2, 'internal-link', 'sulu_io', 'en', 1);

        $this->assertEquals(Structure::NODE_TYPE_INTERNAL_LINK, $structure2->getNodeType());
        $this->assertEquals($structure1->getUuid(), $structure2->getInternalLinkContent()->getUuid());

        $this->assertEquals($structure1->getResourceLocator(), $structure2->getResourceLocator());
        $this->assertEquals($structure1->getNodeName(), $structure2->getNodeName());

        $data3 = [
            'title' => 'Test',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
            'external' => 'www.google.at',
        ];
        $structure3 = $this->mapper->save($data3, 'external-link', 'sulu_io', 'en', 1);

        $this->assertEquals(Structure::NODE_TYPE_EXTERNAL_LINK, $structure3->getNodeType());

        $this->assertEquals('http://www.google.at', $structure3->getResourceLocator());
        $this->assertEquals('Test', $structure3->getNodeName());
    }

    private function prepareSinglePageTestData()
    {
        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'de', 1);
        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'en', 1);

        $data = [
            'title' => 'Page-1',
            'url' => '/page-1',
        ];

        $data = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);

        return $data;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareCopyMoveTestData()
    {
        $data = [
            [
                'title' => 'Page-1',
                'url' => '/page-1',
            ],
            [
                'title' => 'Sub',
                'url' => '/page-1/sub',
            ],
            [
                'title' => 'Sub',
                'url' => '/page-1/sub-1',
            ],
            [
                'title' => 'Page-2',
                'url' => '/page-2',
            ],
            [
                'title' => 'Sub',
                'url' => '/page-2/sub',
            ],
            [
                'title' => 'Sub',
                'url' => '/page-2/sub-1',
            ],
            [
                'title' => 'SubPage',
                'url' => '/page-2/subpage',
            ],
            [
                'title' => 'SubSubPage',
                'url' => '/page-2/subpage/subpage',
            ],
            [
                'title' => 'SubSubSubPage',
                'url' => '/page-2/subpage/subpage/subpage',
            ],
            [
                'title' => 'SubPage',
                'url' => '/page-2/sub-1/subpage',
            ],
            [
                'title' => 'SubSubPage',
                'url' => '/page-2/sub-1/subpage/subpage',
            ],
        ];

        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'de', 1);

        // save content
        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $data[0]->getUuid());
        $data[2] = $this->mapper->save($data[2], 'overview', 'sulu_io', 'de', 1, true, null, $data[0]->getUuid());
        $data[3] = $this->mapper->save($data[3], 'overview', 'sulu_io', 'de', 1);
        $data[4] = $this->mapper->save($data[4], 'overview', 'sulu_io', 'de', 1, true, null, $data[3]->getUuid());
        $data[5] = $this->mapper->save($data[5], 'overview', 'sulu_io', 'de', 1, true, null, $data[3]->getUuid());
        $data[6] = $this->mapper->save($data[6], 'overview', 'sulu_io', 'de', 1, true, null, $data[3]->getUuid());
        $data[7] = $this->mapper->save($data[7], 'overview', 'sulu_io', 'de', 1, true, null, $data[6]->getUuid());
        $data[8] = $this->mapper->save($data[8], 'overview', 'sulu_io', 'de', 1, true, null, $data[7]->getUuid());
        $data[9] = $this->mapper->save($data[9], 'overview', 'sulu_io', 'de', 1, true, null, $data[5]->getUuid());
        $data[10] = $this->mapper->save($data[10], 'overview', 'sulu_io', 'de', 1, true, null, $data[9]->getUuid());

        return $data;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareOrderAtData()
    {
        $data = [
            [
                'title' => 'Page-1',
                'url' => '/page-1',
            ],
            [
                'title' => 'Page-1-1',
                'url' => '/page-1/page-1-1',
            ],
            [
                'title' => 'Page-1-2',
                'url' => '/page-1/page-1-2',
            ],
            [
                'title' => 'Page-1-3',
                'url' => '/page-1/page-1-3',
            ],
            [
                'title' => 'Page-1-4',
                'url' => '/page-1/page-1-4',
            ],
        ];

        $this->saveStartPage(['title' => 'Start Page'], 'overview', 'sulu_io', 'en', 1);

        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'en', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'en', 1, true, null, $data[0]->getUuid());
        $data[2] = $this->mapper->save($data[2], 'overview', 'sulu_io', 'en', 1, true, null, $data[0]->getUuid());
        $data[3] = $this->mapper->save($data[3], 'overview', 'sulu_io', 'en', 1, true, null, $data[0]->getUuid());
        $data[4] = $this->mapper->save($data[4], 'overview', 'sulu_io', 'en', 1, true, null, $data[0]->getUuid());

        return $data;
    }

    public function testMove()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $result = $this->mapper->move($data[6]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'de');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals('/page-1/subpage', $result->url);
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[6]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(1, count($test));

        $test = $this->mapper->loadByParent($data[7]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(1, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(2, count($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        // We need to clear the document manager in order for the moved children to be reloaded
        $this->documentManager->clear();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/subpage', $page2Sub->url);
        $this->assertEquals('/page-1/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-1/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testRenameRlp()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $uuid = $data[6]->getUuid();
        $data[6] = [
            'title' => 'SubPage',
            'url' => '/page-2/test',
        ];
        $result = $data[6] = $this->mapper->save($data[6], 'overview', 'sulu_io', 'de', 2, true, $uuid);

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-2/subpage', $result->getPath());
        $this->assertEquals('/page-2/test', $result->url);
        $this->assertEquals(2, $result->getChanger());

        $this->documentManager->clear();

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(2, count($test));

        $test = $this->mapper->loadByParent($data[6]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(1, count($test));

        $test = $this->mapper->loadByParent($data[7]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(1, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/test', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/test', $page2Sub->url);
        $this->assertEquals('/page-2/test/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/test/subpage/subpage', $page2SubSubSub->url);
    }

    public function testChangeSnippetTemplate()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->move($data[6]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'de');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(2, count($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());
    }

    public function testMoveExistingName()
    {
        $data = $this->prepareCopyMoveTestData();
        $userToken = $this->createUserTokenWithId(2);
        $this->tokenStorage->setToken($userToken);
        $result = $this->mapper->move($data[5]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'de');

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-1-1', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(2, count($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());
    }

    public function testMoveGhostPage()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->move($data[5]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'en');

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-1-1', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $result = $this->mapper->load($result->getUuid(), 'sulu_io', 'en', true);

        $this->assertEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-1-1', $result->getPath());
        $this->assertEquals(2, $result->getChanger());
        $this->assertEquals('ghost', $result->getType()->getName());
        $this->assertEquals('de', $result->getType()->getValue());

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(2, count($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());
    }

    public function testCopy()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $result = $this->mapper->copy($data[6]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'de');

        $this->assertNotEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/subpage', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($result->getUuid(), 'sulu_io', 'de', 2);
        $this->assertCount(2, $test);
        $this->assertEquals('/page-1/subpage/subsubpage', $test[0]->url);
        $this->assertEquals('/page-1/subpage/subsubpage/subsubsubpage', $test[1]->url);

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $test->getResourceLocator());
        $this->assertEquals(1, $test->getChanger());

        $test = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/subpage', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testCopyExistingName()
    {
        $data = $this->prepareCopyMoveTestData();

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);

        $this->tokenStorage->setToken($this->createUserTokenWithId(2));
        $result = $this->mapper->copy($data[5]->getUuid(), $data[0]->getUuid(), 2, 'sulu_io', 'de');

        $this->assertNotEquals($data[5]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/sub-1-1', $result->url);
        $this->assertEquals('/page-1/sub-1-1', $result->getPath());
        $this->assertEquals(2, $result->getChanger());

        $test = $this->mapper->loadByParent($result->getUuid(), 'sulu_io', 'de', 2);
        $this->assertCount(2, $test);
        $this->assertEquals('/page-1/sub-1-1/subpage', $test[0]->url);
        $this->assertEquals('/page-1/sub-1-1/subpage/subsubpage', $test[1]->url);

        $test = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'de', 4, false);
        $this->assertEquals(3, count($test));

        $test = $this->mapper->load($data[5]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/sub-1', $test->getResourceLocator());
        $this->assertEquals(1, $test->getChanger());

        $test = $this->mapper->load($result->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-1/sub-1-1', $test->getResourceLocator());
        $this->assertEquals(2, $test->getChanger());

        $page2Sub = $this->mapper->load($data[6]->getUuid(), 'sulu_io', 'de');
        $page2SubSub = $this->mapper->load($data[7]->getUuid(), 'sulu_io', 'de');
        $page2SubSubSub = $this->mapper->load($data[8]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals('/page-2/subpage', $page2Sub->url);
        $this->assertEquals('/page-2/subpage/subpage', $page2SubSub->url);
        $this->assertEquals('/page-2/subpage/subpage/subpage', $page2SubSubSub->url);
    }

    public function testOrderBefore()
    {
        $data = $this->prepareCopyMoveTestData();

        $result = $this->mapper->orderBefore($data[6]->getUuid(), $data[4]->getUuid(), 4, 'sulu_io', 'en');

        $this->assertEquals($data[6]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-2/subpage', $result->getPath());
        $this->assertEquals(4, $result->getChanger());

        $result = $this->mapper->loadByParent($data[3]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/page-2/subpage', $result[0]->getPath());
        $this->assertEquals('/page-2/sub', $result[1]->getPath());
        $this->assertEquals('/page-2/sub-1', $result[2]->getPath());
    }

    public function testOrderAt()
    {
        $this->tokenStorage->setToken($this->createUserTokenWithId(17));
        $data = $this->prepareOrderAtData();

        $result = $this->mapper->orderAt($data[2]->getUuid(), 3, 17, 'sulu_io', 'en');
        $this->assertEquals($data[2]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/page-1-2', $result->getPath());
        $this->assertEquals(17, $result->getChanger());

        $result = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/page-1/page-1-1', $result[0]->getPath());
        $this->assertEquals('/page-1/page-1-3', $result[1]->getPath());
        $this->assertEquals('/page-1/page-1-2', $result[2]->getPath());
        $this->assertEquals('/page-1/page-1-4', $result[3]->getPath());
    }

    public function testOrderAtInternalLink()
    {
        $this->tokenStorage->setToken($this->createUserTokenWithId(17));
        $data = $this->prepareOrderAtData();

        $this->documentManager->clear();

        $testSiteData = [
            'title' => 'Test',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'url' => '/test/123',
            'internal_link' => $data[0]->getUuid(),
        ];
        $site = $this->mapper->save(
            $testSiteData,
            'internal_link_page',
            'sulu_io',
            'en',
            1,
            true,
            null,
            $data[0]->getUuid()
        );

        $this->documentManager->clear();

        $result = $this->mapper->orderAt($site->getUuid(), 3, 17, 'sulu_io', 'en');
        $this->assertEquals($site->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/test', $result->getPath());
        $this->assertEquals(17, $result->getChanger());

        $this->documentManager->clear();

        $result = $this->documentManager->find($site->getUuid(), 'en');
        $this->assertEquals(30, $result->getSuluOrder());
        $this->assertNull($result->getResourceSegment());

        $result = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/page-1/page-1-1', $result[0]->getPath());
        $this->assertEquals('/page-1/page-1-2', $result[1]->getPath());
        $this->assertEquals('/page-1/test', $result[2]->getPath());
        $this->assertEquals('/page-1/page-1-3', $result[3]->getPath());
        $this->assertEquals('/page-1/page-1-4', $result[4]->getPath());
    }

    public function testOrderAtToLast()
    {
        $data = $this->prepareOrderAtData();

        $result = $this->mapper->orderAt($data[2]->getUuid(), 4, 1, 'sulu_io', 'en');
        $this->assertEquals($data[2]->getUuid(), $result->getUuid());
        $this->assertEquals('/page-1/page-1-2', $result->getPath());
        $this->assertEquals(1, $result->getChanger());

        $result = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'en');
        $this->assertEquals('/page-1/page-1-1', $result[0]->getPath());
        $this->assertEquals('/page-1/page-1-3', $result[1]->getPath());
        $this->assertEquals('/page-1/page-1-4', $result[2]->getPath());
        $this->assertEquals('/page-1/page-1-2', $result[3]->getPath());
    }

    public function testNewExternalLink()
    {
        $data = [
            'title' => 'Page-1',
            'external' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
            'url' => '/url',
        ];

        $saveResult = $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'sulu_io', 'de');

        // check save result
        $this->assertEquals('Page-1', $saveResult->title);
        $this->assertEquals('Page-1', $saveResult->getNodeName());
        $this->assertEquals('www.google.at', $saveResult->external);
        $this->assertEquals('http://www.google.at', $saveResult->getResourceLocator());

        // check load result
        $this->assertEquals('Page-1', $loadResult->title);
        $this->assertEquals('Page-1', $loadResult->getNodeName());
        $this->assertEquals('www.google.at', $loadResult->external);
        $this->assertEquals('http://www.google.at', $loadResult->getResourceLocator());
    }

    public function testChangeToExternalLink()
    {
        // prepare a page
        $data = [
            'title' => 'Page-1',
            'url' => '/page-1',
        ];
        $result = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1);

        // turn it into a external link
        $data = [
            'title' => 'External',
            'external' => 'www.google.at',
            'nodeType' => Structure::NODE_TYPE_EXTERNAL_LINK,
        ];
        $saveResult = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1, true, $result->getUuid());
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'sulu_io', 'en');

        // check save result
        $this->assertEquals('External', $saveResult->title);
        $this->assertEquals('External', $saveResult->getNodeName());
        $this->assertEquals('www.google.at', $saveResult->external);
        $this->assertEquals('http://www.google.at', $saveResult->getResourceLocator());
        $this->assertEquals('overview', $saveResult->getOriginTemplate());

        // check load result
        $this->assertEquals('External', $loadResult->title);
        $this->assertEquals('External', $loadResult->getNodeName());
        $this->assertEquals('www.google.at', $loadResult->external);
        $this->assertEquals('http://www.google.at', $loadResult->getResourceLocator());
        $this->assertEquals('overview', $loadResult->getOriginTemplate());

        // back to content type
        $data = [
            'title' => 'Page-1',
            'nodeType' => Structure::NODE_TYPE_CONTENT,
        ];
        $saveResult = $this->mapper->save($data, 'overview', 'sulu_io', 'en', 1, true, $result->getUuid());
        $loadResult = $this->mapper->load($saveResult->getUuid(), 'sulu_io', 'en');

        // check load result
        $this->assertEquals('Page-1', $loadResult->title);
        $this->assertEquals('Page-1', $loadResult->getNodeName());
        $this->assertEquals('/page-1', $loadResult->url);
        $this->assertEquals('/page-1', $loadResult->getResourceLocator());
    }

    public function testSaveInvalidResourceLocator()
    {
        $data = [
            'title' => 'Testname',
            'tags' => [
                'tag1',
                'tag2',
            ],
            'url' => '/news/test.xml',
            'article' => 'sulu_io',
        ];

        $this->setExpectedException(
            'Sulu\Component\Content\Exception\ResourceLocatorNotValidException',
            "ResourceLocator '/news/test.xml' is not valid"
        );
        $this->mapper->save($data, 'overview', 'sulu_io', 'de', 1);
    }

    public function testSaveSlash()
    {
        $result = $this->mapper->save(
            ['title' => 'My / Your nice test', 'url' => '/my-your-nice-test'],
            'overview',
            'sulu_io',
            'de',
            1
        );

        $this->assertEquals('/my-your-nice-test', $result->getPath());
        $this->assertEquals('/my-your-nice-test', $result->getPropertyValue('url'));
        $this->assertEquals('My / Your nice test', $result->getPropertyValue('title'));
    }

    public function testGetResourceLocators()
    {
        $data = [
            ['title' => 'Beschreibung', 'url' => '/beschreibung'],
            ['title' => 'Description', 'url' => '/description'],
        ];

        $data[0] = $this->mapper->save(
            $data[0],
            'overview',
            'sulu_io',
            'de',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[0] = $this->mapper->load($data[0]->getUuid(), 'sulu_io', 'de');
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[0] = $this->mapper->load($data[0]->getUuid(), 'sulu_io', 'en', true);
        $urls = $data[0]->getUrls();

        $this->assertArrayNotHasKey('en', $urls);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->save(
            $data[1],
            'overview',
            'sulu_io',
            'en',
            1,
            true,
            $data[0]->getUuid(),
            null,
            Structure::STATE_PUBLISHED
        );
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->load($data[1]->getUuid(), 'sulu_io', 'en');
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);

        $data[1] = $this->mapper->load($data[1]->getUuid(), 'sulu_io', 'de', true);
        $urls = $data[1]->getUrls();

        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);
    }

    public function testContentTypeSwitch()
    {
        // REF
        $internalLinkData = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];
        $internalLink = $this->mapper->save($internalLinkData, 'default', 'sulu_io', 'en', 1);

        // REF
        $snippetData = [
            'title' => 'Test',
            'url' => '/test/test',
            'blog' => 'Thats a good test',
        ];
        $snippet = $this->mapper->save(
            $snippetData,
            'hotel',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            null,
            null,
            null,
            Structure::TYPE_SNIPPET
        );

        // Internal Link with String Type
        $testSiteData = [
            'title' => 'Test',
            'nodeType' => Structure::NODE_TYPE_INTERNAL_LINK,
            'url' => '/test/123',
            'internal_link' => $internalLink->getUuid(),
        ];
        $testSiteStructure = $this->mapper->save($testSiteData, 'internal_link_page', 'sulu_io', 'en', 1);

        $uuid = $testSiteStructure->getUuid();

        // Change to Snippet Array
        $testSiteData['internal'] = [
            $snippet->getUuid(),
            $snippet->getUuid(),
        ];
        $testSiteData['nodeType'] = Structure::NODE_TYPE_CONTENT;

        $this->mapper->save($testSiteData, 'with_snippet', 'sulu_io', 'en', 1, true, $uuid);

        // Change to Internal Link String
        $testSiteData['internal'] = $internalLink->getUuid();
        $testSiteData['nodeType'] = Structure::NODE_TYPE_INTERNAL_LINK;
        $this->mapper->save($testSiteData, 'internal-link', 'sulu_io', 'en', 1, true, $uuid);
    }

    /**
     * It should delete a node which has children with history.
     * It should not throw an exception.
     */
    public function testDeleteWithChildrenHistory()
    {
        $data = [
            [
                'title' => 'A',
                'url' => '/a',
            ],
            [
                'title' => 'B',
                'url' => '/a/b',
            ],
            [
                'title' => 'C',
                'url' => '/a/b/c',
            ],
            [
                'title' => 'D',
                'url' => '/a/d',
            ],
        ];

        // save content
        $data[0] = $this->mapper->save($data[0], 'overview', 'sulu_io', 'de', 1);
        $data[1] = $this->mapper->save($data[1], 'overview', 'sulu_io', 'de', 1, true, null, $data[0]->getUuid());
        $data[2] = $this->mapper->save($data[2], 'overview', 'sulu_io', 'de', 1, true, null, $data[1]->getUuid());
        $data[3] = $this->mapper->save($data[3], 'overview', 'sulu_io', 'de', 1, true, null, $data[0]->getUuid());

        // move /a/b to /a/d/b
        $this->mapper->move($data[1]->getUuid(), $data[3]->getUuid(), 1, 'sulu_io', 'de');

        // delete /a/d
        $this->mapper->delete($data[3]->getUuid(), 'sulu_io');

        // check
        try {
            $this->mapper->load($data[3]->getUuid(), 'sulu_io', 'de');
            $this->fail('Node should not exist');
        } catch (DocumentNotFoundException $ex) {
        }

        $result = $this->mapper->loadByParent($data[0]->getUuid(), 'sulu_io', 'de');
        $this->assertEquals(0, count($result));
    }

    /**
     * It should copy a language with an internal link.
     */
    public function testLanguageCopyInternalLink()
    {
        $page = $this->documentManager->create('page');
        $page->setStructureType('default');
        $page->setTitle('Hallo');
        $page->setResourceSegment('/hallo');
        $this->documentManager->persist($page, 'de', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $this->documentManager->flush();

        $data = [
            'title' => 'Page-1',
            'internal_link' => $page->getUuid(),
        ];

        $data = $this->mapper->save($data, 'internal-link', 'sulu_io', 'de', 1);

        $this->mapper->copyLanguage($data->getUuid(), 1, 'sulu_io', 'de', 'en');

        $result = $this->mapper->load($data->getUuid(), 'sulu_io', 'en');

        $this->assertEquals('Page-1', $result->title);
        $this->assertEquals($page->getUuid(), $result->getPropertyValue('internal_link'));
    }

    /**
     * It should return the resource locators including the reosurce locator
     * of the shadow page.
     */
    public function testGetResourceLocatorsWithShadow()
    {
        $page = $this->documentManager->create('page');
        $page->setStructureType('overview');
        $page->setTitle('Beschreibung');
        $page->setResourceSegment('/beschreibung');
        $page->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($page, 'de', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $this->documentManager->flush();

        $page->setTitle('Description');
        $page->setResourceSegment('/description');
        $page->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($page, 'en', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $this->documentManager->flush();

        $page->setShadowLocaleEnabled(true);
        $page->setShadowLocale('de');

        $this->documentManager->persist($page, 'en', [
            'parent_path' => '/cmf/sulu_io/contents',
        ]);
        $this->documentManager->flush();

        $content = $this->mapper->load($page->getUuid(), 'sulu_io', 'en');
        $urls = $content->getUrls();

        $this->assertArrayHasKey('en', $urls);
        $this->assertEquals('/description', $urls['en']);
        $this->assertArrayNotHasKey('en_us', $urls);
        $this->assertArrayHasKey('de', $urls);
        $this->assertEquals('/beschreibung', $urls['de']);
        $this->assertArrayNotHasKey('de_at', $urls);
        $this->assertArrayNotHasKey('es', $urls);
    }

    private function createUserTokenWithId($id)
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->willReturn($id);
        $userToken = new UsernamePasswordToken('test', 'testpass', 'fake_provider');
        $userToken->setUser($user->reveal());

        return $userToken;
    }

    /**
     * @return mixed
     */
    private function getHomeUuid()
    {
        return $this->sessionManager->getContentNode('sulu_io')->getIdentifier();
    }

    private function saveStartPage($data, $templateKey, $webspaceKey, $locale, $userId)
    {
        $this->mapper->save(
            $data,
            $templateKey,
            $webspaceKey,
            $locale,
            $userId,
            true,
            $this->getHomeUuid(),
            null,
            WorkflowStage::PUBLISHED,
            null,
            null,
            'home'
        );
    }
}

class TestExtension extends AbstractExtension
{
    protected $properties = [
        'a',
        'b',
    ];

    public function __construct($name, $additionalPrefix = null)
    {
        $this->name = $name;
        $this->additionalPrefix = $additionalPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function save(NodeInterface $node, $data, $webspaceKey, $languageCode)
    {
        $node->setProperty($this->getPropertyName('a'), $data['a']);
        $node->setProperty($this->getPropertyName('b'), $data['b']);
    }

    /**
     * {@inheritdoc}
     */
    public function load(NodeInterface $node, $webspaceKey, $languageCode)
    {
        return [
            'a' => $node->getPropertyValueWithDefault($this->getPropertyName('a'), ''),
            'b' => $node->getPropertyValueWithDefault($this->getPropertyName('b'), ''),
        ];
    }
}
