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

    public function testRendersLightColorsDuringPreview(): void
    {
        $extension = $this->createExtension(true);

        $this->assertSame(
            '<style>:root{--sulu-preview-deep-link-border:#ff0000;--sulu-preview-deep-link-icon:#000;}</style>',
            $extension->renderDeepLinkColors(['border' => '#ff0000', 'icon' => '#000'])
        );
    }

    public function testRendersDarkColorsForPreferredColorScheme(): void
    {
        $extension = $this->createExtension(true);

        $this->assertSame(
            '<style>:root{--sulu-preview-deep-link-border:#ff0000;}'
            . '@media (prefers-color-scheme: dark){:root{--sulu-preview-deep-link-border:rgb(0, 128, 255);}}</style>',
            $extension->renderDeepLinkColors(['border' => '#ff0000'], ['border' => 'rgb(0, 128, 255)'])
        );
    }

    public function testRendersDarkColorsForSelector(): void
    {
        $extension = $this->createExtension(true);

        $this->assertSame(
            '<style>:root{--sulu-preview-deep-link-border:#ff0000;--sulu-preview-deep-link-icon:#fff;}'
            . 'html[data-theme="dark"]{--sulu-preview-deep-link-icon:#111;}</style>',
            $extension->renderDeepLinkColors(
                ['border' => '#ff0000', 'icon' => '#fff'],
                ['icon' => '#111'],
                'html[data-theme="dark"]'
            )
        );
    }

    public function testRendersNoColorsOutsidePreview(): void
    {
        $extension = $this->createExtension(false);

        $this->assertSame('', $extension->renderDeepLinkColors(['border' => '#ff0000'], ['border' => '#00ff00']));
    }

    public function testRendersNoColorsWithoutRequest(): void
    {
        $extension = new PreviewDeepLinkExtension(new RequestStack());

        $this->assertSame('', $extension->renderDeepLinkColors(['border' => '#ff0000']));
    }

    public function testRendersNothingWithoutColors(): void
    {
        $extension = $this->createExtension(true);

        $this->assertSame('', $extension->renderDeepLinkColors([], []));
    }

    public function testThrowsOnUnknownColor(): void
    {
        $extension = $this->createExtension(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown color "background", expected one of "border", "icon".');

        $extension->renderDeepLinkColors(['background' => '#ff0000']);
    }

    public function testThrowsOnInvalidColorValue(): void
    {
        $extension = $this->createExtension(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for color "border".');

        $extension->renderDeepLinkColors(['border' => 'red;}</style><script>']);
    }

    public function testThrowsOnInvalidDarkSelector(): void
    {
        $extension = $this->createExtension(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid dark selector "html.dark{}".');

        $extension->renderDeepLinkColors(['border' => '#ff0000'], ['border' => '#00ff00'], 'html.dark{}');
    }

    private function createExtension(bool $preview): PreviewDeepLinkExtension
    {
        $request = new Request();
        if ($preview) {
            $request->attributes->set('preview', true);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new PreviewDeepLinkExtension($requestStack);
    }
}
