<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Admin;

use Sulu\Bundle\AdminBundle\Admin\JsConfigInterface;
use Sulu\Bundle\ContentBundle\Admin\WebspaceInputTypesJsConfig;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyInterface;
use Sulu\Component\Content\Types\ResourceLocator\Strategy\ResourceLocatorStrategyPoolInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceInputTypesJsConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ResourceLocatorStrategyPoolInterface
     */
    private $resourceLocatorStrategyPool;

    /**
     * @var JsConfigInterface
     */
    private $jsConfig;

    protected function setUp()
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->resourceLocatorStrategyPool = $this->prophesize(ResourceLocatorStrategyPoolInterface::class);

        $this->jsConfig = new WebspaceInputTypesJsConfig(
            $this->webspaceManager->reveal(),
            $this->resourceLocatorStrategyPool->reveal()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('sulu_content.webspace_input_types', $this->jsConfig->getName());
    }

    public function testGetParameters()
    {
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $strategy = $this->prophesize(ResourceLocatorStrategyInterface::class);
        $strategy->getInputType()->willReturn(ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF);

        $this->webspaceManager->getWebspaceCollection()->willReturn([$webspace->reveal()]);
        $this->resourceLocatorStrategyPool->getStrategyByWebspaceKey('sulu_io')->willReturn($strategy->reveal());

        $this->assertEquals(['sulu_io' => ResourceLocatorStrategyInterface::INPUT_TYPE_LEAF], $this->jsConfig->getParameters());
    }
}
