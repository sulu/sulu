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
                'title' => 'News',
                'url' => '/news'
            ),
            'products' => array(
                'title' => 'Products',
                'url' => '/products'
            ),
            'news/news-1' => array(
                'title' => 'News-1',
                'url' => '/news/news-1'
            ),
            'news/news-2' => array(
                'title' => 'News-2',
                'url' => '/news/news-2'
            ),
            'products/products-1' => array(
                'title' => 'Products-1',
                'url' => '/products/products-1'
            ),
            'products/products-2' => array(
                'title' => 'Products-2',
                'url' => '/products/products-2'
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

    private function getStructureMock()
    {
        $structureMock = $this->getMockForAbstractClass(
            '\Sulu\Component\Content\Structure',
            array('overview', 'asdf', 'asdf', 2400)
        );

        $method = new ReflectionMethod(
            get_class($structureMock), 'add'
        );

        $method->setAccessible(true);
        $method->invokeArgs(
            $structureMock,
            array(
                new Property('title', 'text_line')
            )
        );

        $method->invokeArgs(
            $structureMock,
            array(
                new Property('url', 'resource_locator')
            )
        );

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
}
