<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\DocumentManager\Exception\DocumentNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;

class NavigationTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function activeElementProvider()
    {
        return [
            [false, '/', '/news/item'],
            [true, '/news/item', '/news/item'],
            [true, '/news/item', '/news'],
            [false, '/news/item', '/'],
            [false, '/news/item-1', '/news/item'],
            [false, '/news', '/news/item'],
            [false, '/news', '/product/item'],
            [false, '/news', '/news-1'],
            [false, '/news', '/news-1/item'],
            [true, '/', '/'],
        ];
    }

    /**
     * @dataProvider activeElementProvider
     */
    public function testActiveElement($expected, $requestPath, $itemPath)
    {
        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $this->prophesize(NavigationMapperInterface::class)->reveal()
        );

        $this->assertEquals($expected, $extension->navigationIsActiveFunction($requestPath, $itemPath));
    }

    public function testBreadcrumbFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = $this->prophesize(\Sulu\Component\Webspace\Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $localization = $this->prophesize(\Sulu\Component\Localization\Localization::class);
        $localization->getLocale()->willReturn('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getBreadcrumb('123-123-123', 'sulu_io', 'de')->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->breadcrumbFunction('123-123-123'));
    }

    public function testFlatNavigationFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = $this->prophesize(\Sulu\Component\Webspace\Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $localization = $this->prophesize(\Sulu\Component\Localization\Localization::class);
        $localization->getLocale()->willReturn('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, true, null, false)
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->flatNavigationFunction('123-123-123'));
    }

    public function testTreeNavigationFunctionDocumentNotFound()
    {
        $navigationMapper = $this->prophesize(NavigationMapperInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $navigationMapper->reveal(),
            $requestAnalyzer->reveal()
        );

        $webspace = $this->prophesize(\Sulu\Component\Webspace\Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');
        $requestAnalyzer->getWebspace()->willReturn($webspace->reveal());

        $localization = $this->prophesize(\Sulu\Component\Localization\Localization::class);
        $localization->getLocale()->willReturn('de');
        $requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $navigationMapper->getNavigation('123-123-123', 'sulu_io', 'de', 1, false, null, false)
            ->willThrow(new DocumentNotFoundException());

        $this->assertEquals([], $extension->treeNavigationFunction('123-123-123'));
    }
}
