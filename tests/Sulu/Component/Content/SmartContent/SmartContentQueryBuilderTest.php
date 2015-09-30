<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\SmartContent;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Query\ContentQueryExecutor;
use Sulu\Component\Content\SmartContent\QueryBuilder as SmartContentQueryBuilder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

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
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

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

        $this->mapper = $this->getContainer()->get('sulu.content.mapper');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
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
        $nodes = [];
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

            $request = ContentMapperRequest::create()
                ->setData($data)
                ->setTemplateKey($template)
                ->setWebspaceKey('sulu_io')
                ->setLocale('en')
                ->setUserId(1)
                ->setType('page')
                ->setState(Structure::STATE_PUBLISHED);

            $node = $this->mapper->saveRequest($request);
            $nodes[$node->getUuid()] = $node;
        }

        return $nodes;
    }

    public function testProperties()
    {
        $nodes = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        foreach ($result as $item) {
            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getNodeType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), $item['path']);
            $this->assertEquals($expected->getChanged(), $item['changed']);
            $this->assertEquals($expected->getChanger(), $item['changer']);
            $this->assertEquals($expected->getCreated(), $item['created']);
            $this->assertEquals($expected->getCreator(), $item['creator']);
            $this->assertEquals($expected->getLanguageCode(), $item['locale']);
            $this->assertEquals($expected->getKey(), $item['template']);

            $this->assertEquals($expected->title, $item['title']);
            $this->assertEquals($expected->url, $item['url']);

            if ($expected->hasProperty('article')) {
                $this->assertEquals($expected->article, $item['my_article']);
            }
        }
    }

    public function datasourceProvider()
    {
        $news = $this->mapper->save(
            ['title' => 'News', 'url' => '/news'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $products = $this->mapper->save(
            ['title' => 'Products', 'url' => '/products'],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );

        $nodes = [];
        $max = 15;
        for ($i = 0; $i < $max; ++$i) {
            $data = [
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i,
            ];
            $template = 'simple';
            $node = $this->mapper->save(
                $data,
                $template,
                'sulu_io',
                'en',
                1,
                true,
                null,
                $news->getUuid(),
                Structure::STATE_PUBLISHED
            );
            $nodes[$node->getUuid()] = $node;
        }

        return [$news, $products, $nodes];
    }

    public function testDatasource()
    {
        list($news, $products, $nodes) = $this->datasourceProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        // test news
        $builder->init(['config' => ['dataSource' => $news->getUuid()]]);

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        $this->assertEquals(count($nodes), count($result));
        foreach ($result as $item) {
            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getNodeType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), $item['path']);
            $this->assertEquals($expected->title, $item['title']);
        }

        // test products
        $builder->init(['config' => ['dataSource' => $products->getUuid()]]);

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        $this->assertEquals(0, count($result));
    }

    public function testIncludeSubFolder()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($news, $products, $nodes) = $this->datasourceProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(['config' => ['dataSource' => $root->getIdentifier(), 'includeSubFolders' => true]]);

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        // nodes + news + products
        $this->assertEquals(count($nodes) + 2, count($result));

        $nodes[$news->getUuid()] = $news;
        $nodes[$products->getUuid()] = $products;

        for ($i = 0; $i < count($nodes); ++$i) {
            $item = $result[$i];

            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getNodeType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), $item['path']);
            $this->assertEquals($expected->title, $item['title']);
        }
    }

    public function tagsProvider()
    {
        $nodes = [];
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

            $data = [
                'title' => 'News ' . rand(1, 100),
                'url' => '/news/news-' . $i,
                'ext' => [
                    'excerpt' => [
                        'tags' => $tags,
                    ],
                ],
            ];
            $template = 'simple';
            $node = $this->mapper->save(
                $data,
                $template,
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                Structure::STATE_PUBLISHED
            );
            $nodes[$node->getUuid()] = $node;
        }

        return [$nodes, $t1, $t2, $t1t2];
    }

    public function testTags()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1 and 2
        $builder->init(
            [
                'config' => [
                    'dataSource' => $root->getIdentifier(),
                    'websiteTags' => [$this->tag1->getId(), $this->tag2->getId()],
                    'websiteTagOperator' => 'and',
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
                    'websiteTagOperator' => 'or',
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
                    'websiteTagOperator' => 'or',
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
                    'websiteTagOperator' => 'and',
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
                    'websiteTagOperator' => 'and',
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
                    'websiteTagOperator' => 'and',
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
            $this->webspaceManager,
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
                    'websiteTagOperator' => 'and',
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
                    'websiteTagOperator' => 'OR',
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

        $template = 'simple';
        $nodes = [];
        foreach ($data as $item) {
            $node = $this->mapper->save(
                $item,
                $template,
                'sulu_io',
                'en',
                1,
                true,
                null,
                null,
                Structure::STATE_PUBLISHED
            );
            $nodes[$node->getUuid()] = $node;
        }

        return $nodes;
    }

    public function testCategories()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        $this->categoriesProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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
        $node = $this->mapper->save(
            [
                'title' => 'ASDF',
                'url' => '/asdf-1',
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            [
                'title' => 'QWERTZ',
                'url' => '/qwertz-1',
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            [
                'title' => 'qwertz',
                'url' => '/qwertz',
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            [
                'title' => 'asdf',
                'url' => '/asdf',
            ],
            'simple',
            'sulu_io',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;

        return [$nodes];
    }

    public function testOrderBy()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by title
        $builder->init(
            ['config' => ['dataSource' => $root->getIdentifier(), 'sortBy' => ['title']]]
        );
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);

        $this->assertEquals('ASDF', $result[0]['title']);
        $this->assertEquals('asdf', $result[1]['title']);
        $this->assertEquals('QWERTZ', $result[2]['title']);
        $this->assertEquals('qwertz', $result[3]['title']);

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

        $this->assertEquals('QWERTZ', $result[0]['title']);
        $this->assertEquals('qwertz', $result[1]['title']);
        $this->assertEquals('ASDF', $result[2]['title']);
        $this->assertEquals('asdf', $result[3]['title']);
    }

    public function testOrderByOrder()
    {
        $root = $this->sessionManager->getContentNode('sulu_io');
        list($nodes) = $this->orderByProvider();
        $session = $this->sessionManager->getSession();

        $node = $session->getNodeByIdentifier($nodes['/qwertz']->getUuid());
        $node->setProperty('sulu:order', 10);
        $node = $session->getNodeByIdentifier($nodes['/asdf']->getUuid());
        $node->setProperty('sulu:order', 20);
        $node = $session->getNodeByIdentifier($nodes['/asdf-1']->getUuid());
        $node->setProperty('sulu:order', 30);
        $node = $session->getNodeByIdentifier($nodes['/qwertz-1']->getUuid());
        $node->setProperty('sulu:order', 40);
        $session->save();
        $session->refresh(false);

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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

        $this->assertEquals('qwertz', $result[0]['title']);
        $this->assertEquals('asdf', $result[1]['title']);
        $this->assertEquals('ASDF', $result[2]['title']);
        $this->assertEquals('QWERTZ', $result[3]['title']);

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

        $this->assertEquals('QWERTZ', $result[0]['title']);
        $this->assertEquals('ASDF', $result[1]['title']);
        $this->assertEquals('asdf', $result[2]['title']);
        $this->assertEquals('qwertz', $result[3]['title']);
    }

    public function testExtension()
    {
        $nodes = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('sulu_io', ['en'], $builder);
        $tDiff = microtime(true) - $tStart;

        foreach ($result as $item) {
            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->title, $item['my_title']);
            $this->assertEquals($expected->getExt()['excerpt']['title'], $item['ext_title']);
            $this->assertEquals($expected->getExt()['excerpt']['tags'], $item['ext_tags']);
        }
    }

    public function testIds()
    {
        $nodes = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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
            $this->webspaceManager,
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
        $state = Structure::STATE_PUBLISHED
    ) {
        $node = $this->mapper->save(
            $data,
            'simple',
            'sulu_io',
            $locale,
            1,
            true,
            $uuid,
            $parent,
            $state
        );

        if ($isShadow) {
            $node = $this->mapper->save(
                ['title' => $data['title']],
                'simple',
                'sulu_io',
                $locale,
                1,
                true,
                $uuid,
                $parent,
                $state,
                $isShadow,
                $shadowLocale
            );
        }

        return [$node->getPropertyValue('url') => $node];
    }

    public function testShadow()
    {
        $data = $this->shadowProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
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

        $this->assertEquals(1, count($result));
        $this->assertEquals('/team/daniel', $result[0]['url']);
        $this->assertEquals('Daniel', $result[0]['title']);

        $result = $this->contentQuery->execute('sulu_io', ['de'], $builder);

        $this->assertEquals(2, count($result));
        $this->assertEquals('/team/daniel', $result[0]['url']);
        $this->assertEquals('Daniel', $result[0]['title']);
        $this->assertEquals('/team/johannes', $result[1]['url']);
        $this->assertEquals('Johannes DE', $result[1]['title']);
    }
}
