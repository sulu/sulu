<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Url;

use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Url\WebspaceUrlChainProvider;
use Sulu\Component\Webspace\Url\WebspaceUrlProviderInterface;
use Sulu\Component\Webspace\Webspace;

class WebspaceUrlChainProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUrls()
    {
        $webspace = $this->prophesize(Webspace::class);

        $provider1 = $this->prophesize(WebspaceUrlProviderInterface::class);
        $provider1->getUrls($webspace->reveal(), 'prod')->willReturn([new Url('1.sulu.lo'), new Url('2.sulu.lo')]);
        $provider2 = $this->prophesize(WebspaceUrlProviderInterface::class);
        $provider2->getUrls($webspace->reveal(), 'prod')->willReturn([new Url('3.sulu.lo'), new Url('4.sulu.lo')]);

        $provider = new WebspaceUrlChainProvider([$provider1->reveal(), $provider2->reveal()]);

        $this->assertEquals(
            [new Url('1.sulu.lo'), new Url('2.sulu.lo'), new Url('3.sulu.lo'), new Url('4.sulu.lo')],
            $provider->getUrls($webspace->reveal(), 'prod')
        );
    }

    public function testGetUrlsEmptyChain()
    {
        $webspace = $this->prophesize(Webspace::class);

        $provider = new WebspaceUrlChainProvider();

        $this->assertEquals([], $provider->getUrls($webspace->reveal(), 'prod'));
    }
}
