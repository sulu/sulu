<?php

use Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

class NavigationTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function activeElementProvider()
    {
        return [
            [true, '/news', '/news/item'],
            [false, '/news', '/product/item'],
        ];
    }

    public function testActiveElement($expected, $requestPath, $itemPath)
    {
        $extension = new NavigationTwigExtension(
            $this->prophesize(ContentMapperInterface::class)->reveal(),
            $this->prophesize(NavigationMapperInterface::class)->reveal()
        );

        $this->assertEquals($expected, $extension->navigationIsActiveFunction($requestPath, $itemPath));
    }
}

