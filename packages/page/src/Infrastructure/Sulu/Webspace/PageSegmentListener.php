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

namespace Sulu\Page\Infrastructure\Sulu\Webspace;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class PageSegmentListener implements EventSubscriberInterface
{
    public function __construct(
        private RequestAnalyzerInterface $requestAnalyzer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Run after the routing listener (priority 32) so the matched page
        // is available on the request as the "object" attribute, but before
        // controller resolution and template rendering.
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 6]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $object = $request->attributes->get('object');
        if (!$object instanceof PageDimensionContentInterface) {
            return;
        }

        $pageSegmentKey = $object->getExcerptSegment();
        if (null === $pageSegmentKey || '' === $pageSegmentKey) {
            return;
        }

        $requestAttributes = $request->attributes->get('_sulu');
        if (!$requestAttributes instanceof RequestAttributes) {
            return;
        }

        $webspace = $requestAttributes->getAttribute('webspace');
        if (!$webspace instanceof Webspace) {
            return;
        }

        if (null === $webspace->getSegment($pageSegmentKey)) {
            // The page is assigned to a segment that no longer exists in the
            // webspace configuration; do not change the active segment.
            return;
        }

        $currentSegment = $requestAttributes->getAttribute('segment');
        if ($currentSegment instanceof Segment && $currentSegment->getKey() === $pageSegmentKey) {
            return;
        }

        $this->requestAnalyzer->changeSegment($pageSegmentKey);
    }
}
