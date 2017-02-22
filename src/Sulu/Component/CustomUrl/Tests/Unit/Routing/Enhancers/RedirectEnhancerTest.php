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

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Routing\Enhancers\RedirectEnhancer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class RedirectEnhancerTest extends \PHPUnit_Framework_TestCase
{
    public function testEnhance()
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $customUrl->isRedirect()->willReturn(true);
        $customUrl->getTargetLocale()->willReturn('de');

        $target = $this->prophesize(PageDocument::class);
        $target->getResourceSegment()->willReturn('/test');
        $customUrl->getTargetDocument()->willReturn($target);

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $webspaceManager->findUrlByResourceLocator(
            '/test',
            'prod',
            'de',
            'sulu_io'
        )->willReturn('sulu.io/test');

        $enhancer = new RedirectEnhancer($webspaceManager->reveal());

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal(), '_environment' => 'prod'],
            $request->reveal()
        );

        $this->assertEquals(
            [
                '_custom_url' => $customUrl->reveal(),
                '_webspace' => $webspace->reveal(),
                '_environment' => 'prod',
                '_controller' => 'SuluWebsiteBundle:Redirect:redirect',
                'url' => 'sulu.io/test',
            ],
            $defaults
        );
    }
}
