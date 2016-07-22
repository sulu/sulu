<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Routing\Enhancers;

use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\CustomUrl\Routing\Enhancers\InternalLinkEnhancer;
use Symfony\Component\HttpFoundation\Request;

class InternalLinkEnhancerTest extends \PHPUnit_Framework_TestCase
{
    public function testEnhance()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getNodeType()->willReturn(Structure::NODE_TYPE_INTERNAL_LINK);
        $structure->getResourceLocator()->willReturn('/test');
        $request = $this->prophesize(Request::class);

        $newStructure = $this->prophesize(PageBridge::class);
        $structure->getInternalLinkContent()->willReturn($newStructure);

        $enhancer = new InternalLinkEnhancer();
        $defaults = $enhancer->enhance(['_structure' => $structure->reveal()], $request->reveal());

        $this->assertEquals(
            [
                '_structure' => $newStructure->reveal(),
            ],
            $defaults
        );
    }

    public function testEnhanceNoStructure()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getNodeType()->willReturn(Structure::NODE_TYPE_EXTERNAL_LINK);
        $structure->getResourceLocator()->willReturn('/test');
        $request = $this->prophesize(Request::class);

        $enhancer = new InternalLinkEnhancer();
        $defaults = $enhancer->enhance([], $request->reveal());

        $this->assertEquals([], $defaults);
    }

    public function testEnhanceExternalLink()
    {
        $structure = $this->prophesize(PageBridge::class);
        $structure->getNodeType()->willReturn(Structure::NODE_TYPE_EXTERNAL_LINK);
        $structure->getResourceLocator()->willReturn('/test');
        $request = $this->prophesize(Request::class);

        $enhancer = new InternalLinkEnhancer();
        $defaults = $enhancer->enhance(['_structure' => $structure->reveal()], $request->reveal());

        $this->assertEquals(['_structure' => $structure->reveal()], $defaults);
    }
}
