<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\WebsiteBundle\Twig\NavigationTwigExtension;

class NavigationTwigExtensionTest extends ProphecyTestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $contentMapper;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $navigationMapper;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy
     */
    private $structure;

    /**
     * @var NavigationTwigExtension
     */
    private $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->contentMapper = $this->prophesize('Sulu\Component\Content\Mapper\ContentMapperInterface');
        $this->navigationMapper = $this->prophesize('Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface');
        $this->structure = $this->prophesize('Sulu\Component\Content\StructureInterface');

        $this->extension = new NavigationTwigExtension(
            $this->contentMapper->reveal(), $this->navigationMapper->reveal()
        );
    }

    public function testNavigationFunction()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $this->navigationMapper->getNavigation('123-123-123', 'default', 'en', 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($this->structure->reveal(), 1));
    }

    public function testNavigationFunctionFlatContext()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $this->navigationMapper->getNavigation('123-123-123', 'default', 'en', 1, true, 'test')->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($this->structure->reveal(), 1,null, true, 'test'));
    }

    public function testMainNavigationFunction()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $this->navigationMapper->getRootNavigation('default', 'en', 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->rootNavigationFunction($this->structure->reveal(), 1));
    }

    public function testMainNavigationFunctionFlatContext()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $this->navigationMapper->getRootNavigation('default', 'en', 1, true, 'test')->willReturn(true);

        $this->assertTrue($this->extension->rootNavigationFunction($this->structure->reveal(), 1,true, 'test'));
    }

    public function testNavigationFunctionLevel()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $structure2 = $this->prophesize('Sulu\Component\Content\StructureInterface');
        $structure2->getUuid()->willReturn('321-321-321');

        $this->contentMapper->loadBreadcrumb('123-123-123', 'en', 'default')->willReturn(array(null, null, $structure2, null, null));
        // not ok
        $this->navigationMapper->getNavigation('123-123-123', 'default', 'en', 1, false, null)->willReturn(false);
        // is ok
        $this->navigationMapper->getNavigation('321-321-321', 'default', 'en', 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($this->structure->reveal(), 1, 2));
    }

    public function testBreadcrumb()
    {
        $this->structure->getUuid()->willReturn('123-123-123');
        $this->structure->getWebspaceKey()->willReturn('default');
        $this->structure->getLanguageCode()->willReturn('en');

        $this->navigationMapper->getBreadcrumb('123-123-123', 'default', 'en')->willReturn(true);

        $this->assertTrue($this->extension->breadcrumbFunction($this->structure->reveal()));
    }

}
