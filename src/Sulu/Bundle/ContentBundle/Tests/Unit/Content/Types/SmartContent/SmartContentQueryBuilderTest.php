<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Types;

use ReflectionMethod;
use Sulu\Bundle\ContentBundle\Content\Structure\ExcerptStructureExtension;
use Sulu\Bundle\ContentBundle\Content\Types\SmartContent\SmartContentQueryBuilder;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Query\ContentQuery;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;

class SmartContentQueryBuilderTest extends PhpcrTestCase
{
    /**
     * @var ContentQuery
     */
    private $contentQuery;

    public function setUp()
    {
        $this->prepareMapper();

        $this->structureManager->expects($this->any())
            ->method('getStructures')
            ->will($this->returnCallback(array($this, 'structuresCallback')));

        $this->contentQuery = new ContentQuery(
            $this->sessionManager,
            $this->structureManager,
            $this->templateResolver,
            $this->contentTypeManager,
            $this->languageNamespace
        );
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'simple') {
            return $this->getStructureMock(1, 'simple');
        } elseif ($structureKey == 'article') {
            return $this->getStructureMock(2, 'article');
        } elseif ($structureKey == 'block') {
            return $this->getStructureMock(3, 'block');
        } elseif ($structureKey == 'excerpt') {
            return $this->getStructureMock(4, 'excerpt');
        }

        return null;
    }

    public function structuresCallback()
    {
        return array(
            $this->getStructureMock(1, 'simple'),
            $this->getStructureMock(2, 'article'),
            $this->getStructureMock(3, 'block')
        );
    }

    public function getExtensionsCallback()
    {
        return array(new ExcerptStructureExtension($this->structureManager, $this->contentTypeManager));
    }

    public function getStructureMock($type, $name)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array($name, 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'title', array(), 'text_line', false, true, 1, 1, array(),
                    array(
                        new PropertyTag('sulu.node.name', 1)
                    )
                )
            )
        );

        if ($type === 2) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'article', array(), 'text_area', false, true
                    )
                )
            );
        } elseif ($type === 3) {
            $block = new BlockProperty('article', array(), 'test', false, true, 2, 1);
            $type = new BlockPropertyType('test', array());
            $type->addChild(new Property('title', array(), 'text_line'));
            $type->addChild(new Property('article', array(), 'text_area'));
            $block->addType($type);

            $method->invokeArgs($structureMock, array($block));
        } elseif ($type === 4) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'tags', array(), 'text_line', false, true, 1, 10
                    )
                )
            );
        }

        $method->invokeArgs(
            $structureMock,
            array(
                new Property(
                    'url', 'url', 'resource_locator', false, true, 1, 1, array(),
                    array(
                        new PropertyTag('sulu.rlp', 1)
                    )
                )
            )
        );

        return $structureMock;
    }

    public function propertiesProvider()
    {
        $nodes = array();
        $max = 15;
        for ($i = 0; $i < $max; $i++) {
            $data = array(
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i
            );
            $template = 'simple';

            if ($i > 2 * $max / 3) {
                $template = 'block';
                $data['article'] = array(
                    array(
                        'title' => 'Blocktitle ' . $i,
                        'article' => 'Blockarticle ' . $i,
                        'type' => 'test'
                    ),
                    array(
                        'title' => 'Blocktitle2 ' . $i,
                        'article' => 'Blockarticle2 ' . $i,
                        'type' => 'test'
                    )
                );
            } elseif ($i > $max / 3) {
                $template = 'article';
                $data['article'] = 'Blockarticle ' . $i;
            }

            $node = $this->mapper->save(
                $data,
                $template,
                'default',
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

    public function testProperties()
    {
        $nodes = $this->propertiesProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(array('properties' => array('my_article' => 'article')));

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $tDiff = microtime(true) - $tStart;
        echo("\r\nProperties estimated time (" . sizeof($nodes) . " nodes): " . $tDiff);

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
            array('title' => 'News', 'url' => '/news'),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $products = $this->mapper->save(
            array('title' => 'Products', 'url' => '/products'),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );

        $nodes = array();
        $max = 15;
        for ($i = 0; $i < $max; $i++) {
            $data = array(
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i
            );
            $template = 'simple';
            $node = $this->mapper->save(
                $data,
                $template,
                'default',
                'en',
                1,
                true,
                null,
                $news->getUuid(),
                Structure::STATE_PUBLISHED
            );
            $nodes[$node->getUuid()] = $node;
        }

        return array($news, $products, $nodes);
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
        $builder->init(array('config' => array('dataSource' => $news->getUuid())));

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $tDiff = microtime(true) - $tStart;
        echo("\r\nDatasource estimated time (" . sizeof($nodes) . " nodes): " . $tDiff);

        $this->assertEquals(sizeof($nodes), sizeof($result));
        foreach ($result as $item) {
            /** @var StructureInterface $expected */
            $expected = $nodes[$item['uuid']];

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getNodeType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), $item['path']);
            $this->assertEquals($expected->title, $item['title']);
        }

        // test products
        $builder->init(array('config' => array('dataSource' => $products->getUuid())));

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $tDiff = microtime(true) - $tStart;
        echo("\r\nDatasource estimated time (0 nodes): " . $tDiff);

        $this->assertEquals(0, sizeof($result));
    }

    public function testIncludeSubFolder()
    {
        $root = $this->sessionManager->getContentNode('default');
        list($news, $products, $nodes) = $this->datasourceProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );
        $builder->init(array('config' => array('dataSource' => $root->getIdentifier(), 'includeSubFolders' => true)));

        $tStart = microtime(true);
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $tDiff = microtime(true) - $tStart;
        echo("\r\nIncludeSubFolders estimated time (" . sizeof($nodes) . " nodes): " . $tDiff);

        // nodes + news + products
        $this->assertEquals(sizeof($nodes) + 2, sizeof($result));

        for ($i = 0; $i < sizeof($nodes) + 2; $i++) {
            if ($i === 0) {
                $item = $result[0];

                $expected = $news;
            } elseif ($i === sizeof($nodes) + 1) {
                $item = $result[sizeof($nodes) + 1];

                $expected = $products;
            } else {
                $item = $result[$i];

                /** @var StructureInterface $expected */
                $expected = $nodes[$item['uuid']];
            }

            $this->assertEquals($expected->getUuid(), $item['uuid']);
            $this->assertEquals($expected->getNodeType(), $item['nodeType']);
            $this->assertEquals($expected->getPath(), $item['path']);
            $this->assertEquals($expected->title, $item['title']);
        }
    }

    public function tagsProvider()
    {
        $nodes = array();
        $max = 15;
        $t1t2 = 0;
        $t1 = 0;
        $t2 = 0;
        for ($i = 0; $i < $max; $i++) {
            if ($i % 2 === 1) {
                $tags = array(1, 2);
                $t1t2++;
            } else {
                $tags = array(2);
                $t2++;
            }

            $data = array(
                'title' => 'News ' . $i,
                'url' => '/news/news-' . $i,
                'ext' => array(
                    'excerpt' => array(
                        'tags' => $tags
                    )
                )
            );
            $template = 'simple';
            $node = $this->mapper->save(
                $data,
                $template,
                'default',
                'en',
                1,
                true,
                null,
                null,
                Structure::STATE_PUBLISHED
            );
            $nodes[$node->getUuid()] = $node;
        }

        return array($nodes, $t1, $t2, $t1t2);
    }

    public function testTags()
    {
        $root = $this->sessionManager->getContentNode('default');
        list($nodes, $t1, $t2, $t1t2) = $this->tagsProvider();
        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // tag 1, 2
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'tags' => array(1, 2)))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $this->assertEquals($t1t2, sizeof($result));

        // tag 1
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'tags' => array(1)))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $this->assertEquals($t1t2 + $t1, sizeof($result));

        // tag 2
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'tags' => array(2)))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $this->assertEquals($t1t2 + $t2, sizeof($result));

        // tag 3
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'tags' => array(3)))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);
        $this->assertEquals(0, sizeof($result));
    }

    public function orderByProvider()
    {
        $node = $this->mapper->save(
            array(
                'title' => 'ASDF',
                'url' => '/asdf-1'
            ),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            array(
                'title' => 'QWERTZ',
                'url' => '/qwertz-1'
            ),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            array(
                'title' => 'qwertz',
                'url' => '/qwertz'
            ),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;
        $node = $this->mapper->save(
            array(
                'title' => 'asdf',
                'url' => '/asdf'
            ),
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            Structure::STATE_PUBLISHED
        );
        $nodes[$node->url] = $node;

        return array($nodes);
    }

    public function testOrderBy()
    {
        $root = $this->sessionManager->getContentNode('default');
        list($nodes) = $this->orderByProvider();

        $builder = new SmartContentQueryBuilder(
            $this->structureManager,
            $this->webspaceManager,
            $this->sessionManager,
            $this->languageNamespace
        );

        // order by title
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'orderBy' => array('title')))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);

        $this->assertEquals('ASDF', $result[0]['title']);
        $this->assertEquals('asdf', $result[1]['title']);
        $this->assertEquals('QWERTZ', $result[2]['title']);
        $this->assertEquals('qwertz', $result[3]['title']);

        // order by title and desc
        $builder->init(
            array('config' => array('dataSource' => $root->getIdentifier(), 'sortBy' => array('title'), 'sortMethod' => 'desc'))
        );
        $result = $this->contentQuery->execute('default', array('en'), $builder);

        $this->assertEquals('QWERTZ', $result[0]['title']);
        $this->assertEquals('qwertz', $result[1]['title']);
        $this->assertEquals('ASDF', $result[2]['title']);
        $this->assertEquals('asdf', $result[3]['title']);
    }
}
