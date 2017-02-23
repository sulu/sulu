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

use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Routing\Enhancers\TrailingSlashEnhancer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class TrailingSlashEnhancerTest extends \PHPUnit_Framework_TestCase
{
    public function testEnhance()
    {
        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $webspace = $this->prophesize(Webspace::class);

        $request = $this->prophesize(Request::class);
        $request->getRequestUri()->willReturn('/test/');
        $request->getUri()->willReturn('sulu.io/test/');

        $enhancer = new TrailingSlashEnhancer();

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal()],
            $request->reveal()
        );

        $this->assertEquals(
            [
                '_custom_url' => $customUrl->reveal(),
                '_webspace' => $webspace->reveal(),
                '_finalized' => true,
                '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
                'url' => 'sulu.io/test',
            ],
            $defaults
        );
    }

    public function testEnhanceWithoutSlash()
    {
        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $webspace = $this->prophesize(Webspace::class);

        $request = $this->prophesize(Request::class);
        $request->getRequestUri()->willReturn('/test');

        $enhancer = new TrailingSlashEnhancer();

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal()],
            $request->reveal()
        );

        $this->assertEquals(['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal()], $defaults);
    }
}
