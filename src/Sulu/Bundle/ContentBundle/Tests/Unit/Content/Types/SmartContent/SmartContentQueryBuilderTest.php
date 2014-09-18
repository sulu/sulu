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
use Sulu\Bundle\ContentBundle\Content\Types\SmartContent\SmartContentQueryBuilder;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Block\BlockProperty;
use Sulu\Component\Content\Block\BlockPropertyType;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\Query\ContentQuery;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureInterface;
use vendor\sulu\sulu\tests\Sulu\Component\Content\Block\BlockPropertyTest;

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
                    'title', 'title', 'text_line', false, true, 1, 1, array(),
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
                        'article', 'title', 'text_area', false, true
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
        $news = $this->mapper->save(array('title' => 'News', 'url' => '/news'), 'simple', 'default', 'en', 1);
        $products = $this->mapper->save(
            array('title' => 'Products', 'url' => '/products'),
            'simple',
            'default',
            'en',
            1
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
}
