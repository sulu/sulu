<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Semaphore\Exception\RuntimeException;
use Symfony\Component\Semaphore\SemaphoreFactory;
use Symfony\Component\Semaphore\SemaphoreInterface;

/**
 * Limits the number of HTTP requests generating an image at the same time, to keep the
 * memory used by the image processing under control when many uncached formats are
 * requested at once (e.g. a media collection listed in the administration interface
 * for the first time).
 *
 * A request for the image proxy route takes a slot of a shared semaphore before the
 * controller runs, waiting for a free one if needed, and releases it with the response.
 * When no slot becomes available within the maximum wait time, the request fails:
 * the limit is there to protect the server, generating the image anyway would defeat it.
 *
 * @internal
 */
final class ParallelImageGenerationLimiter implements EventSubscriberInterface
{
    public const IMAGE_PROXY_ROUTE = 'sulu_media.website.image.proxy';

    public const SEMAPHORE_RESOURCE = 'sulu_media.parallel_image_generation';

    private const REQUEST_ATTRIBUTE = '_sulu_media_parallel_image_generation_semaphore';

    /**
     * Microseconds between two attempts to take a free slot.
     */
    private const WAIT_INTERVAL = 50000;

    public function __construct(
        private SemaphoreFactory $semaphoreFactory,
        private int $limit,
        /**
         * Seconds after which a request waiting for a free slot gives up.
         */
        private int $maxWaitTime = 60,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (self::IMAGE_PROXY_ROUTE !== $request->attributes->get('_route')) {
            return;
        }

        $semaphore = $this->semaphoreFactory->createSemaphore(self::SEMAPHORE_RESOURCE, $this->limit);

        $deadline = \microtime(true) + $this->maxWaitTime;
        while (!$semaphore->acquire()) {
            if (\microtime(true) >= $deadline) {
                throw new RuntimeException(\sprintf(
                    'No image generation slot became available within %d seconds.',
                    $this->maxWaitTime,
                ));
            }

            \usleep(self::WAIT_INTERVAL);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $semaphore);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $semaphore = $request->attributes->get(self::REQUEST_ATTRIBUTE);
        if (!$semaphore instanceof SemaphoreInterface) {
            return;
        }

        $request->attributes->remove(self::REQUEST_ATTRIBUTE);
        $semaphore->release();
    }
}
