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
        $uuid = '123-123-123';
        $webspaceKey = 'default';
        $locale = 'en';

        $this->navigationMapper->getNavigation($uuid, $webspaceKey, $locale, 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($uuid, $webspaceKey, $locale));
    }

    public function testNavigationFunctionFlatContext()
    {
        $uuid = '123-123-123';
        $webspaceKey = 'default';
        $locale = 'en';

        $this->navigationMapper->getNavigation($uuid, $webspaceKey, $locale, 1, true, 'test')->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($uuid, $webspaceKey, $locale, 1, null, true, 'test'));
    }

    public function testMainNavigationFunction()
    {
        $webspaceKey = 'default';
        $locale = 'en';

        $this->navigationMapper->getRootNavigation($webspaceKey, $locale, 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->rootNavigationFunction($webspaceKey, $locale, 1));
    }

    public function testMainNavigationFunctionFlatContext()
    {
        $webspaceKey = 'default';
        $locale = 'en';

        $this->navigationMapper->getRootNavigation('default', 'en', 1, true, 'test')->willReturn(true);

        $this->assertTrue($this->extension->rootNavigationFunction($webspaceKey, $locale, 1,true, 'test'));
    }

    public function testNavigationFunctionLevel()
    {
        $uuid = '123-123-123';
        $webspaceKey = 'default';
        $locale = 'en';

        $structure2 = $this->prophesize('Sulu\Component\Content\StructureInterface');
        $structure2->getUuid()->willReturn('321-321-321');

        $this->contentMapper->loadBreadcrumb('123-123-123', 'en', 'default')->willReturn(array(null, null, $structure2, null, null));
        // not ok
        $this->navigationMapper->getNavigation('123-123-123', 'default', 'en', 1, false, null)->willReturn(false);
        // is ok
        $this->navigationMapper->getNavigation('321-321-321', 'default', 'en', 1, false, null)->willReturn(true);

        $this->assertTrue($this->extension->navigationFunction($uuid, $webspaceKey, $locale, 1, 2));
    }

    public function testBreadcrumb()
    {
        $uuid = '123-123-123';
        $webspaceKey = 'default';
        $locale = 'en';;

        $this->navigationMapper->getBreadcrumb('123-123-123', 'default', 'en')->willReturn(true);

        $this->assertTrue($this->extension->breadcrumbFunction($uuid, $webspaceKey, $locale));
    }
}
