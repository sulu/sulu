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

use Sulu\Component\Webspace\Environment;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Url;
use Sulu\Component\Webspace\Webspace;

class WebspaceUrlProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUrls()
    {
        $urls = [
            new Url('sulu.lo'),
            new Url('*.sulu.lo'),
            new Url('sulu.io'),
            new Url('*.sulu.io'),
        ];

        $environments = [$this->prophesize(Environment::class), $this->prophesize(Environment::class)];
        $portals = [$this->prophesize(Portal::class), $this->prophesize(Portal::class)];
        $portals[0]->getEnvironment('prod')->willReturn($environments[0]->reveal());
        $portals[1]->getEnvironment('prod')->willReturn($environments[1]->reveal());

        $environments[0]->getUrls()->willReturn([$urls[0], $urls[1]]);
        $environments[1]->getUrls()->willReturn([$urls[2], $urls[3]]);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getPortals()->willReturn(
            array_map(
                function ($portal) {
                    return $portal->reveal();
                },
                $portals
            )
        );

        $provider = new Url\WebspaceUrlProvider();
        $this->assertEquals($urls, $provider->getUrls($webspace->reveal(), 'prod'));
    }
}
