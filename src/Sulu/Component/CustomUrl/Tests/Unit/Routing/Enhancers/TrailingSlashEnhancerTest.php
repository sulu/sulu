<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Tests\Unit\Routing\Enhancers;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Routing\Enhancers\TrailingSlashEnhancer;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class TrailingSlashEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public function testEnhance(): void
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
                '_controller' => 'sulu_website.redirect_controller::redirectAction',
                'url' => 'sulu.io/test',
            ],
            $defaults
        );
    }

    public function testEnhanceWithoutSlash(): void
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
