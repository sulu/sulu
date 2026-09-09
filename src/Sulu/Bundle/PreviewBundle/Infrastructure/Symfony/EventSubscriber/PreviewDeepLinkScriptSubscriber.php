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
 * Injects the preview deep-link bridge script into the admin preview iframe response. Only the
 * admin preview render (route "sulu_preview.render") gets it: the public/shareable preview render
 * ("sulu_preview.public_render") has no admin form on the other end to navigate to.
 *
 * @internal No BC promises are given for this class. It may be changed or removed at any time.
 */
final class PreviewDeepLinkScriptSubscriber implements EventSubscriberInterface
{
    private const RENDER_ROUTE = 'sulu_preview.render';
    private const SCRIPT_PATH = '/bundles/sulupreview/js/preview-deep-link.js';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (self::RENDER_ROUTE !== $event->getRequest()->attributes->get('_route')) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();
        if (!\is_string($content)) {
            return;
        }

        $position = \strripos($content, '</body>');
        if (false === $position) {
            return;
        }

        $script = \sprintf('<script src="%s"></script>', self::SCRIPT_PATH);

        $response->setContent(
            \substr($content, 0, $position) . $script . \substr($content, $position)
        );
    }
}
