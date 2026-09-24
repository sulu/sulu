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

namespace Sulu\Bundle\PreviewBundle\Infrastructure\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injects the deep-link bridge script into the admin preview iframe, on every render (the "render"
 * route and the JSON "update" routes) since the admin rewrites the whole document each change. Only
 * content that uses sulu_preview_deep_link() gets it, so the overlay never alters previews that
 * cannot navigate; the public preview route is left out as it has no admin form to reach.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class PreviewDeepLinkScriptSubscriber implements EventSubscriberInterface
{
    private const RENDER_ROUTE = 'sulu_preview.render';

    /**
     * Routes whose response is a JSON object with the rendered content under a "content" key.
     */
    private const UPDATE_ROUTES = ['sulu_preview.update', 'sulu_preview.update-context'];

    private const DEEP_LINK_ATTRIBUTE = 'data-sulu-preview-id';

    private const SCRIPT_PATH = '/bundles/sulupreview/js/preview-deep-link.js';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        $response = $event->getResponse();
        $content = $response->getContent();
        if (!\is_string($content)) {
            return;
        }

        if (self::RENDER_ROUTE === $route) {
            $injected = $this->injectScript($content);
            if (null !== $injected) {
                $response->setContent($injected);
            }

            return;
        }

        if (\in_array($route, self::UPDATE_ROUTES, true)) {
            $decoded = \json_decode($content, true);
            if (!\is_array($decoded) || !isset($decoded['content']) || !\is_string($decoded['content'])) {
                return;
            }

            $injected = $this->injectScript($decoded['content']);
            if (null === $injected) {
                return;
            }

            $decoded['content'] = $injected;
            $encoded = \json_encode($decoded, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            if (false !== $encoded) {
                $response->setContent($encoded);
            }
        }
    }

    private function injectScript(string $content): ?string
    {
        // Skip content without a navigable target so the overlay is not appended to its body.
        if (!\str_contains($content, self::DEEP_LINK_ATTRIBUTE)) {
            return null;
        }

        $position = \strripos($content, '</body>');
        if (false === $position) {
            return null;
        }

        $script = \sprintf('<script src="%s"></script>', self::SCRIPT_PATH);

        return \substr($content, 0, $position) . $script . \substr($content, $position);
    }
}
