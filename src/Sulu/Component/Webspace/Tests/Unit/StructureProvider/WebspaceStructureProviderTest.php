<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\StructureProvider;

use Doctrine\Common\Cache\ArrayCache;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;
use Sulu\Component\Webspace\Webspace;

class WebspaceStructureProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetStructures()
    {
        $cache = new ArrayCache();

        $structures = [
            $this->generateStructure('t1', 'MyBundle:default:t1'),
            $this->generateStructure('t2', 'MyBundle:default:t2'),
            $this->generateStructure('t3', 'MyBundle:default:t3'),
        ];

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getTheme()->willReturn('test');

        $twigLoader = $this->prophesize(\Twig_LoaderInterface::class);
        $twigLoader->getSource('MyBundle:default:t1.html.twig')->willThrow(new \Twig_Error_Loader('Missing template'));
        $twigLoader->getSource('MyBundle:default:t2.html.twig')->shouldBeCalled();
        $twigLoader->getSource('MyBundle:default:t3.html.twig')->willThrow(new \Twig_Error_Loader('Missing template'));

        $twig = $this->prophesize('\Twig_Environment');
        $twig->getLoader()->willReturn($twigLoader->reveal());

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructures()->willReturn($structures);

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $cache
        );

        $result = $structureProvider->getStructures('sulu_io');

        $this->assertCount(1, $result);
        $this->assertEquals($structures[1], $result[0]);

        $this->assertTrue($cache->contains('sulu_io'));
        $this->assertEquals(['t2'], $cache->fetch('sulu_io'));
    }

    public function testGetStructuresCached()
    {
        $cache = new ArrayCache();
        $cache->save('sulu_io', ['t1', 't3']);

        $structures = [
            $this->generateStructure('t1', 'MyBundle:default:t1'),
            $this->generateStructure('t2', 'MyBundle:default:t2'),
            $this->generateStructure('t3', 'MyBundle:default:t3'),
        ];

        $twig = $this->prophesize(\Twig_Environment::class);
        $twig->getLoader()->shouldNotBeCalled();

        $structureManager = $this->prophesize(StructureManagerInterface::class);
        $structureManager->getStructures()->shouldNotBeCalled();
        $structureManager->getStructure('t1')->willReturn($structures[0]);
        $structureManager->getStructure('t2')->shouldNotBeCalled();
        $structureManager->getStructure('t3')->willReturn($structures[2]);

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $cache
        );

        $result = $structureProvider->getStructures('sulu_io');

        $this->assertCount(2, $result);
        $this->assertEquals($structures[0]->getKey(), $result[0]->getKey());
        $this->assertEquals($structures[2]->getKey(), $result[1]->getKey());
    }

    /**
     * @param string $key
     * @param string $view
     *
     * @return StructureInterface
     */
    private function generateStructure($key, $view)
    {
        $mock = $this->prophesize(PageBridge::class);

        $mock->getKey()->willReturn($key);
        $mock->getView()->willReturn($view);

        return $mock->reveal();
    }
}
