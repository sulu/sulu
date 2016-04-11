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
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Webspace\StructureProvider\WebspaceStructureProvider;

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

        $theme = $this->prophesize('Sulu\Component\Webspace\Theme');
        $theme->getKey()->willReturn('test');

        $webspace = $this->prophesize('Sulu\Component\Webspace\Webspace');
        $webspace->getTheme()->willReturn($theme->reveal());

        $twigLoader = $this->prophesize('\Twig_LoaderInterface');
        $twigLoader->getSource('MyBundle:default:t1.html.twig')->willThrow(new \Twig_Error_Loader('Missing template'));
        $twigLoader->getSource('MyBundle:default:t2.html.twig')->shouldBeCalled();
        $twigLoader->getSource('MyBundle:default:t3.html.twig')->willThrow(new \Twig_Error_Loader('Missing template'));

        $twig = $this->prophesize('\Twig_Environment');
        $twig->getLoader()->willReturn($twigLoader->reveal());

        $structureManager = $this->prophesize('Sulu\Component\Content\Compat\StructureManagerInterface');
        $structureManager->getStructures()->willReturn($structures);

        $webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $webspaceManager->findWebspaceByKey('sulu_io')->willReturn($webspace->reveal());

        $activeTheme = $this->prophesize('Liip\ThemeBundle\ActiveTheme');
        $activeTheme->getName()->willReturn('before');
        $activeTheme->setName('test')->shouldBeCalled();
        $activeTheme->setName('before')->shouldBeCalled();

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $webspaceManager->reveal(),
            $activeTheme->reveal(),
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

        $twig = $this->prophesize('\Twig_Environment');
        $twig->getLoader()->shouldNotBeCalled();

        $structureManager = $this->prophesize('Sulu\Component\Content\Compat\StructureManagerInterface');
        $structureManager->getStructures()->shouldNotBeCalled();
        $structureManager->getStructure('t1')->willReturn($structures[0]);
        $structureManager->getStructure('t2')->shouldNotBeCalled();
        $structureManager->getStructure('t3')->willReturn($structures[2]);

        $webspaceManager = $this->prophesize('Sulu\Component\Webspace\Manager\WebspaceManagerInterface');
        $webspaceManager->findWebspaceByKey('sulu_io')->shouldNotBeCalled();

        $activeTheme = $this->prophesize('Liip\ThemeBundle\ActiveTheme');
        $activeTheme->getName()->shouldNotBeCalled();
        $activeTheme->setName('test')->shouldNotBeCalled();
        $activeTheme->setName('before')->shouldNotBeCalled();

        $structureProvider = new WebspaceStructureProvider(
            $twig->reveal(),
            $structureManager->reveal(),
            $webspaceManager->reveal(),
            $activeTheme->reveal(),
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
        $mock = $this->prophesize('Sulu\Component\Content\Compat\Structure\PageBridge');

        $mock->getKey()->willReturn($key);
        $mock->getView()->willReturn($view);

        return $mock->reveal();
    }
}
