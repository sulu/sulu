<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

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
}
