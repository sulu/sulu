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

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Infrastructure\Symfony\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber\PreviewDeepLinkScriptSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PreviewDeepLinkScriptSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private const SCRIPT = '<script src="/bundles/sulupreview/js/preview-deep-link.js"></script>';

    private function handle(?string $route, string $content): Response
    {
        $request = new Request();
        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        $response = new Response($content);

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        (new PreviewDeepLinkScriptSubscriber())->onKernelResponse($event);

        return $event->getResponse();
    }

    public function testInjectsScriptForAdminRenderRoute(): void
    {
        $response = $this->handle('sulu_preview.render', '<html><body><h1>Hello</h1></body></html>');

        $this->assertSame(
            '<html><body><h1>Hello</h1>' . self::SCRIPT . '</body></html>',
            $response->getContent()
        );
    }

    public function testInjectsScriptIntoJsonContentForUpdateRoute(): void
    {
        $json = (string) \json_encode(['content' => '<html><body><h1>Hello</h1></body></html>']);
        $response = $this->handle('sulu_preview.update', $json);

        $this->assertSame(
            ['content' => '<html><body><h1>Hello</h1>' . self::SCRIPT . '</body></html>'],
            \json_decode((string) $response->getContent(), true)
        );
    }

    public function testInjectsScriptIntoJsonContentForUpdateContextRoute(): void
    {
        $json = (string) \json_encode(['content' => '<html><body>a</body></html>']);
        $response = $this->handle('sulu_preview.update-context', $json);

        $this->assertSame(
            ['content' => '<html><body>a' . self::SCRIPT . '</body></html>'],
            \json_decode((string) $response->getContent(), true)
        );
    }

    public function testDoesNotTouchUpdateJsonWithoutContentKey(): void
    {
        $json = (string) \json_encode(['other' => 'value']);
        $response = $this->handle('sulu_preview.update', $json);

        $this->assertSame($json, $response->getContent());
    }

    public function testInjectsBeforeLastBodyTag(): void
    {
        $response = $this->handle('sulu_preview.render', '<body>a</body><body>b</body>');

        $this->assertSame('<body>a</body><body>b' . self::SCRIPT . '</body>', $response->getContent());
    }

    public function testDoesNotInjectForPublicRenderRoute(): void
    {
        $content = '<html><body><h1>Hello</h1></body></html>';
        $response = $this->handle('sulu_preview.public_render', $content);

        $this->assertSame($content, $response->getContent());
    }

    public function testDoesNotInjectWithoutRoute(): void
    {
        $content = '<html><body><h1>Hello</h1></body></html>';
        $response = $this->handle(null, $content);

        $this->assertSame($content, $response->getContent());
    }

    public function testDoesNotInjectWhenNoBodyTag(): void
    {
        $content = '{"content": "partial update"}';
        $response = $this->handle('sulu_preview.render', $content);

        $this->assertSame($content, $response->getContent());
    }
}
