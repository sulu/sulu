<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Infrastructure\Symfony\Twig;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\Twig\PreviewDeepLinkExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewDeepLinkExtensionTest extends TestCase
{
    public function testRendersAttributeDuringPreview(): void
    {
        $request = new Request();
        $request->attributes->set('preview', true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $extension = new PreviewDeepLinkExtension($requestStack);

        $this->assertSame(
            'data-sulu-preview-id="abc123"',
            $extension->renderDeepLinkAttribute('abc123')
        );
    }

    public function testRendersNothingOutsidePreview(): void
    {
        $request = new Request();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $extension = new PreviewDeepLinkExtension($requestStack);

        $this->assertSame('', $extension->renderDeepLinkAttribute('abc123'));
    }

    public function testRendersNothingWithoutId(): void
    {
        $request = new Request();
        $request->attributes->set('preview', true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $extension = new PreviewDeepLinkExtension($requestStack);

        $this->assertSame('', $extension->renderDeepLinkAttribute(null));
    }

    public function testEscapesId(): void
    {
        $request = new Request();
        $request->attributes->set('preview', true);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $extension = new PreviewDeepLinkExtension($requestStack);

        $this->assertSame(
            'data-sulu-preview-id="&quot;&gt;&lt;script&gt;"',
            $extension->renderDeepLinkAttribute('"><script>')
        );
    }

    public function testRendersNothingWithoutRequest(): void
    {
        $requestStack = new RequestStack();

        $extension = new PreviewDeepLinkExtension($requestStack);

        $this->assertSame('', $extension->renderDeepLinkAttribute('abc123'));
    }
}
