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
 * Injects the preview deep-link bridge script into the admin preview iframe content. The admin
 * preview replaces the whole iframe document on every change (the "update" routes return the
 * rendered content as JSON that is written into the iframe), so the script has to be part of every
 * render, not only the initial one. The public/shareable preview uses a different route
 * ("sulu_preview.public_render") and is intentionally left out: it has no admin form to navigate to.
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
            $encoded = \json_encode($decoded);
            if (false !== $encoded) {
                $response->setContent($encoded);
            }
        }
    }

    private function injectScript(string $content): ?string
    {
        $position = \strripos($content, '</body>');
        if (false === $position) {
            return null;
        }

        $script = \sprintf('<script src="%s"></script>', self::SCRIPT_PATH);

        return \substr($content, 0, $position) . $script . \substr($content, $position);
    }
}
