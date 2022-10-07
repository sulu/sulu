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
use Sulu\Component\CustomUrl\Routing\Enhancers\SeoEnhancer;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class SeoEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public function testEnhance(): void
    {
        $request = $this->prophesize(Request::class);
        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $customUrl->isRedirect()->willReturn(true);
        $customUrl->getTargetLocale()->willReturn('de');
        $customUrl->isNoFollow()->willReturn(true);
        $customUrl->isNoIndex()->willReturn(true);
        $customUrl->isCanonical()->willReturn(false);

        $target = $this->prophesize(PageDocument::class);
        $target->getResourceSegment()->willReturn('/test');
        $customUrl->getTargetDocument()->willReturn($target);

        $webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $enhancer = new SeoEnhancer($webspaceManager->reveal());

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal(), '_environment' => 'prod'],
            $request->reveal()
        );

        $this->assertEquals(
            [
                '_custom_url' => $customUrl->reveal(),
                '_webspace' => $webspace->reveal(),
                '_environment' => 'prod',
                '_seo' => [
                    'noFollow' => true,
                    'noIndex' => true,
                ],
            ],
            $defaults
        );
    }

    public function testEnhanceWithCanonical(): void
    {
        $request = $this->prophesize(Request::class);
        $request->getHost()->willReturn('sulu.io');
        $request->getScheme()->willReturn('http');

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu_io');

        $customUrl = $this->prophesize(CustomUrlDocument::class);
        $customUrl->getTargetLocale()->willReturn('de');
        $customUrl->isNoFollow()->willReturn(false);
        $customUrl->isNoIndex()->willReturn(true);
        $customUrl->isCanonical()->willReturn(true);

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

        $enhancer = new SeoEnhancer($webspaceManager->reveal());

        $defaults = $enhancer->enhance(
            ['_custom_url' => $customUrl->reveal(), '_webspace' => $webspace->reveal(), '_environment' => 'prod'],
            $request->reveal()
        );

        $this->assertEquals(
            [
                '_custom_url' => $customUrl->reveal(),
                '_webspace' => $webspace->reveal(),
                '_environment' => 'prod',
                '_seo' => [
                    'noFollow' => false,
                    'noIndex' => true,
                    'canonicalUrl' => 'sulu.io/test',
                ],
            ],
            $defaults
        );
    }
}
