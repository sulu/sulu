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
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;
use Sulu\Component\CustomUrl\Routing\Enhancers\RedirectEnhancer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class RedirectEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public function testEnhance(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.io');
        $request->getScheme()->willReturn('http');
        $request->getRequestFormat(null)->willReturn(null);
        $request->getQueryString()->willReturn(null);

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
            'sulu_io',
            'sulu.io',
            'http'
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
                '_controller' => 'sulu_website.redirect_controller::redirectAction',
                'url' => 'sulu.io/test',
            ],
            $defaults
        );
    }

    public function testEnhanceWithQueryString(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.io');
        $request->getScheme()->willReturn('http');
        $request->getRequestFormat(null)->willReturn(null);
        $request->getQueryString()->willReturn('param=1');

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
            'sulu_io',
            'sulu.io',
            'http'
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
                '_controller' => 'sulu_website.redirect_controller::redirectAction',
                'url' => 'sulu.io/test?param=1',
            ],
            $defaults
        );
    }

    public function testEnhanceWithRequestFormatAndQueryString(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.io');
        $request->getScheme()->willReturn('http');
        $request->getRequestFormat(null)->willReturn('json');
        $request->getQueryString()->willReturn('param=1');

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
            'sulu_io',
            'sulu.io',
            'http'
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
                '_controller' => 'sulu_website.redirect_controller::redirectAction',
                'url' => 'sulu.io/test.json?param=1',
            ],
            $defaults
        );
    }
}
