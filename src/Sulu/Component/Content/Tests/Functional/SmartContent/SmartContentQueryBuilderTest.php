<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\SmartContent;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\SmartContent\QueryBuilder as SmartContentQueryBuilder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * @group functional
 * @group content
 */
class SmartContentQueryBuilderTest extends SuluTestCase
{
    /**
     * @var ContentQueryExecutor
     */
    private $contentQuery;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var Tag
     */
    private $tag1;

    /**
     * @var Tag
     */
    private $tag2;

    /**
     * @var Tag
     */
    private $tag3;

    public function setUp()
    {
        parent::setUp();

        $this->purgeDatabase();
        $this->initPhpcr();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->extensionManager = $this->getContainer()->get('sulu_content.extension.manager');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->contentQuery = $this->getContainer()->get('sulu.content.query_executor');

        $this->languageNamespace = $this->getContainer()->getParameter('sulu.content.language.namespace');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository('Sulu\Bundle\SecurityBundle\Entity\User')->findOneByUsername('test');

        $this->tag1 = new Tag();
        $this->tag1->setName('test1');
        $this->tag1->setCreator($user);
        $this->tag1->setChanger($user);
        $em->persist($this->tag1);

        $this->tag2 = new Tag();
        $this->tag2->setName('test2');
        $this->tag2->setCreator($user);
        $this->tag2->setChanger($user);
        $em->persist($this->tag2);

        $this->tag3 = new Tag();
        $this->tag3->setName('test3');
        $this->tag3->setCreator($user);
        $this->tag3->setChanger($user);
        $em->persist($this->tag3);

        $em->flush();
    }

    public function propertiesProvider()
    {
        $documents = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            $data = [
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i,
                'ext' => [
                    'excerpt' => [
                        'title' => 'Excerpt Title ' . $i,
                        'tags' => [],
                    ],
                ],
            ];
            $template = 'simple';

            if ($i > 2 * $max / 3) {
                $template = 'block';
                $data['article'] = [
                    [
                        'title' => 'Block Title ' . $i,
                        'article' => 'Blockarticle ' . $i,
                        'type' => 'test',
                    ],
                    [
                        'title' => 'Block Title 2 ' . $i,
                        'article' => 'Blockarticle2 ' . $i,
                        'type' => 'test',
                    ],
                ];
            } elseif ($i > $max / 3) {
                $template = 'article';
                $data['article'] = 'Text article ' . $i;
            }

            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle($data['title']);
            $document->getStructure()->bind($data);
            $document->setStructureType($template);
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);

            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');

            $documents[$document->getUuid()] = $document;
        }

        $this->documentManager->flush();

        return $documents;
    }

    public function testProperties()
    {
        $documents = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'properties' => [
                    'my_article' => new PropertyParameter('my_article', 'article'),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        foreach ($result as $item) {
            /** @var PageDocument $expectedDocument */
            $expectedDocument = $documents[$item['uuid']];

            $this->assertEquals($expectedDocument->getUuid(), $item['uuid']);
            $this->assertEquals($expectedDocument->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expectedDocument->getChanged(), $item['changed']);
            $this->assertEquals($expectedDocument->getChanger(), $item['changer']);
            $this->assertEquals($expectedDocument->getCreated(), $item['created']);
            $this->assertEquals($expectedDocument->getCreator(), $item['creator']);
            $this->assertEquals($expectedDocument->getLocale(), $item['locale']);
            $this->assertEquals($expectedDocument->getStructureType(), $item['template']);

            $this->assertEquals($expectedDocument->getPath(), '/cmf/sulu_io/contents' . $item['path']);

            $this->assertEquals($expectedDocument->getTitle(), $item['title']);
            $this->assertEquals($expectedDocument->getResourceSegment(), $item['url']);

            if ($expectedDocument->getStructure()->hasProperty('article')) {
                $this->assertEquals(
                    $expectedDocument->getStructure()->getProperty('article')->getValue(),
                    $item['my_article']
                );
            }
        }
    }

    public function datasourceProvider()
    {
        /** @var PageDocument $news */
        $news = $this->documentManager->create('page');
        $news->setTitle('News');
        $news->setResourceSegment('/news');
        $news->setStructureType('simple');
        $news->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($news, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($news, 'en');
        $this->documentManager->flush();

        /** @var PageDocument $products */
        $products = $this->documentManager->create('page');
        $products->setTitle('Products');
        $products->setResourceSegment('/products');
        $products->setStructureType('simple');
        $products->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($products, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($products, 'en');
        $this->documentManager->flush();

        $documents = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle('News ' . $i);
            $document->setResourceSegment('/news/news-' . $i);
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $document->setParent($news);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents/news']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return [$news, $products, $documents];
    }

    public function testDatasource()
    {
        list($news, $products, $nodes) = $this->datasourceProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        // test news
        $builder->init(['config' => ['dataSource' => $news->getUuid()]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(count($nodes), count($result));
        foreach ($result as $item) {
            /** @var PageDocument $expectedDocument */
            $expectedDocument = $nodes[$item['uuid']];

            $this->assertEquals($expectedDocument->getUuid(), $item['uuid']);
            $this->assertEquals($expectedDocument->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expectedDocument->getPath(), '/cmf/sulu_io/contents' . $item['path']);
            $this->assertEquals($expectedDocument->getTitle(), $item['title']);
        }

        // test products
        $builder->init(['config' => ['dataSource' => $products->getUuid()]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(0, count($result));
    }

    public function testIncludeSubFolder()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($news, $products, $nodes) = $this->datasourceProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['config' => ['dataSource' => $root->getIdentifier(), 'includeSubFolders' => true]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        // nodes + news + products
        $this->assertEquals(count($nodes) + 2, count($result));

        $nodes[$news->getUuid()] = $news;
        $nodes[$products->getUuid()] = $products;

        for ($i = 0; $i < count($nodes); ++$i) {
            $item = $result[$i];

            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getRedirectType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), '/cmf/sulu_io/contents' . $item['path']);
            $this->assertEquals($expected->getTitle(), $item['title']);
        }
    }

    public function tagsProvider()
    {
        $documents = [];
        $max = 15;
        $t1t2 = 0;
        $t1 = 0;
        $t2 = 0;
        for ($i = 0; $i < $max; ++$i) {
            if ($i % 3 === 2) {
                $tags = [$this->tag1->getName()];
                ++$t1;
            } elseif ($i % 3 === 1) {
                $tags = [$this->tag1->getName(), $this->tag2->getName()];
                ++$t1t2;
            } else {
                $tags = [$this->tag2->getName()];
                ++$t2;
            }

            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle('News ' . rand(1, 100));
            $document->setResourceSegment('/news/news-' . $i);
            $document->setExtensionsData(
                [
                    'excerpt' => [
                        'tags' => $tags,
                    ],
                ]
            );
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return [$documents, $t1, $t2, $t1t2];
    }

    public function testTags()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1, 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'tagOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, count($result));

        // tag 1
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag1->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1, count($result));

        // tag 2
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag2->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, count($result));

        // tag 3
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'tags' => [$this->tag3->getId()], 'tagOperator' => 'and']]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(0, count($result));
    }

    public function testWebsiteTags()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1 and 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, count($result));

        // tag 1 or 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1 + $t2, count($result));

        // tag 3 or 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag3->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t2 + $t1t2, count($result)); // no t3 pages there

        // tag 1
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t1, count($result));

        // tag 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, count($result));

        // tag 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag3->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(0, count($result));
    }

    public function testTagsBoth()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId()],
                    'tagOperator' => 'and',
                    'websiteTags' => [$this->tag2->getId()],
                    'websiteTagsOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2, count($result));

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'tags' => [$this->tag1->getId()],
                    'tagOperator' => 'and',
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagsOperator' => 'OR',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals($t1t2 + $t2, count($result));
    }

    public function categoriesProvider()
    {
        $data = [
            [
                'title' => 'News 1',
                'url' => '/news-1',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1],
                    ],
                ],
            ],
            [
                'title' => 'News 2',
                'url' => '/news-2',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1, 2],
                    ],
                ],
            ],
            [
                'title' => 'News 3',
                'url' => '/news-3',
                'ext' => [
                    'excerpt' => [
                        'categories' => [1, 3],
                    ],
                ],
            ],
            [
                'title' => 'News 4',
                'url' => '/news-4',
                'ext' => [
                    'excerpt' => [
                        'categories' => [3],
                    ],
                ],
            ],
        ];

        $documents = [];
        foreach ($data as $item) {
            /** @var PageDocument $document */
            $document = $this->documentManager->create('page');
            $document->setTitle($item['title']);
            $document->setResourceSegment($item['url']);
            $document->setExtensionsData($item['ext']);
            $document->setStructureType('simple');
            $document->setWorkflowStage(WorkflowStage::PUBLISHED);
            $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
            $this->documentManager->publish($document, 'en');
            $this->documentManager->flush();

            $documents[$document->getUuid()] = $document;
        }

        return $documents;
    }

    public function testCategories()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        $this->categoriesProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // category 1
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(3, count($result));
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(3, count($result));

        // category 1 and 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 2],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(1, count($result));

        // category 1 or 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 3],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(4, count($result));

        // category 1 and 3
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [1, 3],
                    'categoryOperator' => 'and',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(1, count($result));

        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'categories' => [],
                    'categoryOperator' => 'or',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $this->assertEquals(4, count($result));
    }

    public function orderByProvider()
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setTitle('A');
        $document->setResourceSegment('/a');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('Z');
        $document->setResourceSegment('/z');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('y');
        $document->setResourceSegment('/y');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $this->documentManager->flush();
        $documents[$document->getResourceSegment()] = $document;

        $document = $this->documentManager->create('page');
        $document->setTitle('b');
        $document->setResourceSegment('/b');
        $document->setStructureType('simple');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->publish($document, 'en');
        $documents[$document->getResourceSegment()] = $document;
        $this->documentManager->flush();

        return [$documents];
    }

    public function testOrderBy()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by title
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'sortBy' => ['title']]]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('A', $result[0]['title']);
        $this->assertEquals('b', $result[1]['title']);
        $this->assertEquals('y', $result[2]['title']);
        $this->assertEquals('Z', $result[3]['title']);

        // order by title and desc
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'sortBy' => ['title'],
                    'sortMethod' => 'desc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('Z', $result[0]['title']);
        $this->assertEquals('y', $result[1]['title']);
        $this->assertEquals('b', $result[2]['title']);
        $this->assertEquals('A', $result[3]['title']);
    }

    public function testOrderByOrder()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();
        $session = $this->sessionManager->getSession();

        $node = $session->getNodeByIdentifier($nodes['/y']->getUuid());
        $node->setProperty('sulu:order', 10);
        $node = $session->getNodeByIdentifier($nodes['/b']->getUuid());
        $node->setProperty('sulu:order', 20);
        $node = $session->getNodeByIdentifier($nodes['/a']->getUuid());
        $node->setProperty('sulu:order', 30);
        $node = $session->getNodeByIdentifier($nodes['/z']->getUuid());
        $node->setProperty('sulu:order', 40);
        $session->save();
        $session->refresh(false);

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by default
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'orderBy' => [],
                    'sortMethod' => 'asc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('y', $result[0]['title']);
        $this->assertEquals('b', $result[1]['title']);
        $this->assertEquals('A', $result[2]['title']);
        $this->assertEquals('Z', $result[3]['title']);

        // order by default
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'orderBy' => [],
                    'sortMethod' => 'desc',
                ],
            ]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('Z', $result[0]['title']);
        $this->assertEquals('A', $result[1]['title']);
        $this->assertEquals('b', $result[2]['title']);
        $this->assertEquals('y', $result[3]['title']);
    }

    public function testExtension()
    {
        /** @var PageDocument[] $documents */
        $documents = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'properties' => [
                    'my_title' => new PropertyParameter('my_title', 'title'),
                    'ext_title' => new PropertyParameter('ext_title', 'excerpt.title'),
                    'ext_tags' => new PropertyParameter('ext_tags', 'excerpt.tags'),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        foreach ($result as $item) {
            $expectedDocument = $documents[$item['uuid']];

            $this->assertEquals($expectedDocument->getTitle(), $item['my_title']);
            $this->assertEquals($expectedDocument->getExtensionsData()['excerpt']['title'], $item['ext_title']);
            $this->assertEquals($expectedDocument->getExtensionsData()['excerpt']['tags'], $item['ext_tags']);
        }
    }

    public function testIds()
    {
        $nodes = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['ids' => [array_keys($nodes)[0], array_keys($nodes)[1]]]);

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        $this->assertEquals(2, count($result));
        $this->assertArrayHasKey($result[0]['uuid'], $nodes);
        $this->assertArrayHasKey($result[1]['uuid'], $nodes);
    }

    public function testExcluded()
    {
        $nodes = $this->propertiesProvider();
        $uuids = array_keys($nodes);

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['excluded' => [$uuids[0]]]);

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(14, count($result));
        unset($uuids[0]);
        foreach ($result as $item) {
            $this->assertContains($item['uuid'], $uuids);
        }
    }

    private function shadowProvider()
    {
        $nodesEn = [];
        $nodesDe = [];
        $nodesEn = array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Team',
                    'url' => '/team',
                ],
                'en'
            )
        );
        $nodesEn = array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Thomas',
                    'url' => '/team/thomas',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid(),
                false,
                null,
                Structure::STATE_TEST
            )
        );
        $nodesEn = array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Daniel',
                    'url' => '/team/daniel',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid()
            )
        );
        $nodesEn = array_merge(
            $nodesEn,
            $this->save(
                [
                    'title' => 'Johannes',
                    'url' => '/team/johannes',
                ],
                'en',
                null,
                $nodesEn['/team']->getUuid(),
                false,
                null,
                Structure::STATE_TEST
            )
        );

        $nodesDe = array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Team',
                    'url' => '/team',
                ],
                'de',
                $nodesEn['/team']->getUuid(),
                null,
                true,
                'en'
            )
        );
        $nodesDe = array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'url' => '/team/thomas',
                ],
                'de',
                $nodesEn['/team/thomas']->getUuid(),
                null,
                true,
                'en'
            )
        );
        $nodesDe = array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'not-important',
                    'url' => '/team/daniel',
                ],
                'de',
                $nodesEn['/team/daniel']->getUuid(),
                null,
                true,
                'en'
            )
        );
        $nodesDe = array_merge(
            $nodesDe,
            $this->save(
                [
                    'title' => 'Johannes DE',
                    'url' => '/team/johannes',
                ],
                'de',
                $nodesEn['/team/johannes']->getUuid()
            )
        );

        return ['en' => $nodesEn, 'de' => $nodesDe];
    }

    private function save(
        $data,
        $locale,
        $uuid = null,
        $parent = null,
        $isShadow = false,
        $shadowLocale = '',
        $state = WorkflowStage::PUBLISHED
    ) {
        if (!$isShadow) {
            /** @var PageDocument $document */
            try {
                $document = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            } catch (DocumentNotFoundException $e) {
                $document = $this->documentManager->create('page');
            }
            $document->getStructure()->bind($data);
            $document->setTitle($data['title']);
            $document->setResourceSegment($data['url']);
            $document->setStructureType('simple');
            $document->setWorkflowStage($state);

            $persistOptions = [];
            if ($parent) {
                $document->setParent($this->documentManager->find($parent));
            } elseif (!$document->getParent()) {
                $persistOptions['parent_path'] = '/cmf/sulu_io/contents';
            }
            $this->documentManager->persist($document, $locale, $persistOptions);
        } else {
            $document = $this->documentManager->find($uuid, $locale, ['load_ghost_content' => false]);
            $document->setShadowLocaleEnabled(true);
            $document->setShadowLocale($shadowLocale);
            $document->setLocale($locale);
            $this->documentManager->persist($document, $locale);
        }

        if ($state === WorkflowStage::PUBLISHED) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return [$document->getResourceSegment() => $document];
    }

    public function testShadow()
    {
        $data = $this->shadowProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->extensionManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(
            [
                'ids' => [
                    $data['en']['/team/thomas']->getUuid(),
                    $data['en']['/team/daniel']->getUuid(),
                    $data['en']['/team/johannes']->getUuid(),
                ],
            ]
        );

        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals(3, count($result));
        $this->assertEquals('/team/thomas', $result[0]['url']);
        $this->assertEquals('Thomas', $result[0]['title']);
        $this->assertEquals(false, $result[0]['publishedState']);
        $this->assertNull($result[0]['published']);
        $this->assertEquals('/team/daniel', $result[1]['url']);
        $this->assertEquals('Daniel', $result[1]['title']);
        $this->assertEquals(true, $result[1]['publishedState']);
        $this->assertNotNull($result[1]['published']);
        $this->assertEquals('/team/johannes', $result[2]['url']);
        $this->assertEquals('Johannes', $result[2]['title']);
        $this->assertEquals(false, $result[2]['publishedState']);
        $this->assertNull($result[2]['published']);

        $result = $this->contentQuery->execute('sulu_io', ['de'], $builder);

        $this->assertEquals(3, count($result));
        $this->assertEquals('/team/thomas', $result[0]['url']);
        $this->assertEquals('Thomas', $result[0]['title']);
        $this->assertEquals(false, $result[0]['publishedState']);
        $this->assertNull($result[0]['published']);
        $this->assertEquals('/team/daniel', $result[1]['url']);
        $this->assertEquals('Daniel', $result[1]['title']);
        $this->assertEquals(true, $result[1]['publishedState']);
        $this->assertNotNull($result[1]['published']);
        $this->assertEquals('/team/johannes', $result[2]['url']);
        $this->assertEquals('Johannes DE', $result[2]['title']);
        $this->assertEquals(true, $result[2]['publishedState']);
        $this->assertNotNull($result[2]['published']);
    }
}
