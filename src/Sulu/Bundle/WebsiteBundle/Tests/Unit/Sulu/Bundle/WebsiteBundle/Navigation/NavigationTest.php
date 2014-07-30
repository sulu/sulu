<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use ReflectionMethod;
use Sulu\Bundle\TestBundle\Testing\PhpcrTestCase;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyTag;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Webspace;

class NavigationTest extends PhpcrTestCase
{
    /**
     * @var StructureInterface[]
     */
    private $data;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var NavigationMapperInterface
     */
    private $navigation;

    protected function setUp()
    {
        $this->prepareMapper();
        $this->data = $this->prepareTestData();

        $this->navigation = new NavigationMapper($this->mapper);
    }

    protected function prepareWebspaceManager()
    {
        if ($this->webspaceManager === null) {
            $this->webspace = new Webspace();
            $this->webspace->setKey('default');

            $local = new Localization();
            $local->setLanguage('en');

            $this->webspace->setLocalizations(array($local));
            $this->webspace->setName('Default');

            $this->webspaceManager = $this->getMock('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
            $this->webspaceManager
                ->expects($this->any())
                ->method('findWebspaceByKey')
                ->will($this->returnValue($this->webspace));
        }
    }

    public function structureCallback()
    {
        $args = func_get_args();
        $structureKey = $args[0];

        if ($structureKey == 'default_template') {
            return $this->getStructureMock();
        } elseif ($structureKey == 'simple') {
            return $this->getStructureMock();
        } elseif ($structureKey == 'norlp') {
            return $this->getStructureMock(false);
        }

        return null;
    }

    /**
     * @return StructureInterface[]
     */
    private function prepareTestData()
    {
        $data = array(
            'news' => array(
                'name' => 'News',
                'rl' => '/news'
            ),
            'products' => array(
                'name' => 'Products',
                'rl' => '/products'
            ),
            'news/news-1' => array(
                'name' => 'News-1',
                'rl' => '/news/news-1'
            ),
            'news/news-2' => array(
                'name' => 'News-2',
                'rl' => '/news/news-2'
            ),
            'products/products-1' => array(
                'name' => 'Products-1',
                'rl' => '/products/products-1'
            ),
            'products/products-2' => array(
                'name' => 'Products-2',
                'rl' => '/products/products-2'
            )
        );

        $data['news'] = $this->mapper->save(
            $data['news'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED,
            'main'
        );
        $data['news/news-1'] = $this->mapper->save(
            $data['news/news-1'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED,
            'main'
        );
        $data['news/news-2'] = $this->mapper->save(
            $data['news/news-2'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            $data['news']->getUuid(),
            StructureInterface::STATE_PUBLISHED,
            'main'
        );

        $data['products'] = $this->mapper->save(
            $data['products'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED,
            true
        );
        $data['products/products-1'] = $this->mapper->save(
            $data['products/products-1'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED,
            'main'
        );
        $data['products/products-2'] = $this->mapper->save(
            $data['products/products-2'],
            'simple',
            'default',
            'en',
            1,
            true,
            null,
            $data['products']->getUuid(),
            StructureInterface::STATE_PUBLISHED,
            'main'
        );

        return $data;
    }

    private function getStructureMock($rlp = true)
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'addChild'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property('name', '', 'text_line', false, false, 1, 1, array(), array(new PropertyTag('sulu.node.name', 1)))
            )
        );

        if ($rlp) {
            $method->invokeArgs(
                $structureMock,
                array(
                    new Property(
                        'rl',
                        '',
                        'resource_locator',
                        false,
                        false,
                        1,
                        1,
                        array(),
                        array(new PropertyTag('sulu.rlp', 1))
                    )
                )
            );
        }

        return $structureMock;
    }

    public function testMainNavigation()
    {
        $main = $this->navigation->getMainNavigation('default', 'en', 2);
        $this->assertEquals(2, sizeof($main));
        $this->assertEquals(2, sizeof($main[0]->getChildren()));
        $this->assertEquals(2, sizeof($main[1]->getChildren()));

        $this->assertEquals('/news', $main[0]->getUrl());
        $this->assertEquals('/news/news-1', $main[0]->getChildren()[0]->getUrl());
        $this->assertEquals('/news/news-2', $main[0]->getChildren()[1]->getUrl());
        $this->assertEquals('/products', $main[1]->getUrl());
        $this->assertEquals('/products/products-1', $main[1]->getChildren()[0]->getUrl());
        $this->assertEquals('/products/products-2', $main[1]->getChildren()[1]->getUrl());

        $main = $this->navigation->getMainNavigation('default', 'en', 1);
        $this->assertEquals(2, sizeof($main));
        $this->assertEquals(0, sizeof($main[0]->getChildren()));
        $this->assertEquals(0, sizeof($main[1]->getChildren()));

        $main = $this->navigation->getMainNavigation('default', 'en', null);
        $this->assertEquals(2, sizeof($main));
        $this->assertEquals(2, sizeof($main[0]->getChildren()));
        $this->assertEquals(2, sizeof($main[1]->getChildren()));
        $this->assertEquals(0, sizeof($main[0]->getChildren()[0]->getChildren()));
        $this->assertEquals(0, sizeof($main[0]->getChildren()[1]->getChildren()));
        $this->assertEquals(0, sizeof($main[1]->getChildren()[0]->getChildren()));
        $this->assertEquals(0, sizeof($main[1]->getChildren()[1]->getChildren()));
    }

    public function testNavigation()
    {
        $main = $this->navigation->getNavigation($this->data['news']->getUuid(), 'default', 'en', 1);
        $this->assertEquals(2, sizeof($main));
        $this->assertEquals(0, sizeof($main[0]->getChildren()));
        $this->assertEquals(0, sizeof($main[1]->getChildren()));

        $this->assertEquals($this->data['news/news-1']->getUuid(), $main[0]->getId());
        $this->assertEquals('News-1', $main[0]->getTitle());
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $main[0]->getContent());
        $this->assertEquals('/news/news-1', $main[0]->getUrl());

        $this->assertEquals($this->data['news/news-2']->getUuid(), $main[1]->getId());
        $this->assertEquals('News-2', $main[1]->getTitle());
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $main[1]->getContent());
        $this->assertEquals('/news/news-2', $main[1]->getUrl());
    }

    public function testNavigationNoRlp()
    {
        // this node should not be visible in navigation
        $this->mapper->save(
            array('name' => 'Hikaru Sulu'),
            'norlp',
            'default',
            'en',
            1,
            true,
            null,
            null,
            StructureInterface::STATE_PUBLISHED,
            'main'
        );

        $main = $this->navigation->getNavigation($this->data['news']->getUuid(), 'default', 'en', 1);
        $this->assertEquals(2, sizeof($main));
        $this->assertEquals(0, sizeof($main[0]->getChildren()));
        $this->assertEquals(0, sizeof($main[1]->getChildren()));

        $this->assertEquals($this->data['news/news-1']->getUuid(), $main[0]->getId());
        $this->assertEquals('News-1', $main[0]->getTitle());
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $main[0]->getContent());
        $this->assertEquals('/news/news-1', $main[0]->getUrl());

        $this->assertEquals($this->data['news/news-2']->getUuid(), $main[1]->getId());
        $this->assertEquals('News-2', $main[1]->getTitle());
        $this->assertInstanceOf('Sulu\Component\Content\StructureInterface', $main[1]->getContent());
        $this->assertEquals('/news/news-2', $main[1]->getUrl());
    }
}
